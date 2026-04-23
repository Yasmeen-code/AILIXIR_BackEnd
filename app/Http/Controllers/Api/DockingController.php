<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Docking\SubmitDockingRequest;
use App\Jobs\RunDockingJob;
use App\Models\DockingJob;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

class DockingController
{
    use ApiResponseTrait;

    /**
     * Submit a docking job (accepts ligand file OR SMILES string).
     */
    public function submit(SubmitDockingRequest $request)
    {
        $isSmiles = $request->filled('ligand_smiles');

        // ---- Handle Ligand ----
        if ($isSmiles) {
            // SMILES → PDBQT conversion (synchronous, fast ~1-3s)
            $conversion = $this->convertSmilesToPdbqt($request->ligand_smiles);

            if ($conversion['status'] === 'error') {
                return $this->errorResponse(
                    'SMILES conversion failed: ' . $conversion['message'],
                    422
                );
            }

            $ligandPath = $conversion['output_file'];
        } else {
            $ligandFilename = Str::random(40) . '.pdbqt';
            $ligandPath = storage_path('app/private/' . $request->file('ligand_file')->storeAs('docking', $ligandFilename));
        }

        // ---- Handle Protein ----
        $proteinFilename = Str::random(40) . '.pdbqt';
        $proteinPath = storage_path('app/private/' . $request->file('protein_file')->storeAs('docking', $proteinFilename));

        // ---- Create Database Record ----
        $job = DockingJob::create([
            'user_id'      => $request->user()->id,
            'input_type'   => $isSmiles ? 'smiles' : 'file',
            'smiles'       => $isSmiles ? $request->ligand_smiles : null,
            'protein_name' => $request->protein_name,
            'ligand_name'  => $request->ligand_name,
            'protein_path' => $proteinPath,
            'ligand_path'  => $ligandPath,
            'status'       => 'pending',
        ]);

        // ---- Dispatch background docking job ----
        RunDockingJob::dispatch($job, [
            'center_x' => $request->center_x,
            'center_y' => $request->center_y,
            'center_z' => $request->center_z,
            'box_size_x' => $request->box_size_x,
            'box_size_y' => $request->box_size_y,
            'box_size_z' => $request->box_size_z,
            'exhaustiveness' => $request->exhaustiveness ?? 8,
            'n_poses' => $request->n_poses ?? 5,
        ]);

        return $this->successResponse('Docking Job Successfully Queued', [
            'job_id' => $job->id,
            'status' => $job->status,
        ]);
    }

    /**
     * List all docking jobs for the authenticated user (excludes SMILES-only conversion jobs).
     */
    public function history(Request $request)
    {
        $perPage = min((int) $request->query('per_page', 15), 100);

        $paginator = DockingJob::where('user_id', $request->user()->id)
            ->where(function ($q) {
                // Exclude SMILES-only conversion jobs (those belong to convert-smiles)
                $q->where('input_type', '!=', 'smiles')
                  ->orWhereNotNull('protein_name');
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        $items = collect($paginator->items())->map(function ($job) {
            $data = [
                'job_id'     => $job->id,
                'status'     => $job->status,
                'inputs'     => [
                    'protein' => $job->protein_name,
                    'ligand'  => $job->ligand_name,
                ],
                'created_at' => $job->created_at->toIso8601String(),
            ];

            if ($job->status === 'completed' && $job->result_data) {
                $data['results'] = [
                    'vina_scores'  => $job->result_data['vina_score'] ?? [],
                    'download_url' => url('/api/docking/download/' . $job->id),
                ];
            }

            return $data;
        });

        return $this->successResponse('Docking history retrieved successfully', [
            'data'       => $items,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
                'has_more'     => $paginator->hasMorePages(),
            ],
        ]);
    }

    /**
     * Get docking job status.
     */
    public function status(Request $request, $id)
    {
        $job = DockingJob::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->where(function ($q) {
                $q->where('input_type', '!=', 'smiles')
                  ->orWhereNotNull('protein_name');
            })
            ->first();

        if (! $job) {
            return $this->errorResponse('Docking job not found or unauthorized', 404);
        }

        // Build results block (only when job is completed)
        $results = null;
        if ($job->status === 'completed' && $job->result_data) {
            $results = [
                'vina_scores'  => $job->result_data['vina_score'] ?? [],
                'download_url' => url('/api/docking/download/' . $job->id),
            ];
        }

        $data = [
            'job_id'     => $job->id,
            'status'     => $job->status,
            'inputs'     => [
                'protein' => $job->protein_name,
                'ligand'  => $job->ligand_name,
            ],
            'created_at' => $job->created_at->toIso8601String(),
        ];

        if ($results !== null) {
            $data['results'] = $results;
        }

        return $this->successResponse('Job details retrieved successfully', $data);
    }

    /**
     * Download docking result or converted PDBQT.
     */
    public function download(Request $request, $id)
    {
        $job = DockingJob::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->where(function ($q) {
                $q->where('input_type', '!=', 'smiles')
                  ->orWhereNotNull('protein_name');
            })
            ->first();

        if (! $job || $job->status !== 'completed') {
            return $this->errorResponse('Docking file not available or unauthorized', 404);
        }

        $filePath = $job->result_data['output_file'] ?? null;

        if (! $filePath || ! file_exists($filePath)) {
            return $this->errorResponse('File not found on server', 404);
        }

        return response()->download($filePath, 'docking_result_' . $job->id . '.pdbqt');
    }

    /**
     * Internal helper: run the SMILES → PDBQT Python conversion.
     *
     * @return array{status: string, output_file?: string, message?: string}
     */
    private function convertSmilesToPdbqt(string $smiles): array
    {
        $pythonPath = env('DOCKING_PYTHON_PATH', base_path('vina_env/bin/python'));
        $scriptPath = env('SMILES_SCRIPT_PATH', base_path('scripts/smiles_to_pdbqt.py'));

        // Generate unique output path
        $outputFilename = Str::random(40) . '.pdbqt';
        $outputDir = storage_path('app/private/docking/generated');
        $outputPath = $outputDir . '/' . $outputFilename;

        // Ensure output directory exists
        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $cmd = sprintf(
            '%s %s %s %s',
            escapeshellarg($pythonPath),
            escapeshellarg($scriptPath),
            escapeshellarg($smiles),
            escapeshellarg($outputPath)
        );

        try {
            $result = Process::timeout(120)->run($cmd);

            $outputData = null;
            $fullOutput = $result->output();

            if (preg_match('/\{"status":\s*"(success|error)".*\}/s', $fullOutput, $matches)) {
                $outputData = json_decode($matches[0], true);
            }

            if (! $result->successful()) {
                $errorMsg = $outputData['message'] ?? $result->errorOutput() ?: 'Unknown conversion error';
                Log::error('SMILES conversion failed', ['smiles' => $smiles, 'error' => $errorMsg]);
                return ['status' => 'error', 'message' => $errorMsg];
            }

            if ($outputData && $outputData['status'] === 'success') {
                return $outputData;
            }

            if ($outputData && $outputData['status'] === 'error') {
                return $outputData;
            }

            return ['status' => 'error', 'message' => 'Unexpected output from conversion script'];

        } catch (\Exception $e) {
            Log::error('SMILES conversion exception', ['smiles' => $smiles, 'error' => $e->getMessage()]);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
