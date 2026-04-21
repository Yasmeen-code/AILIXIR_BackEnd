<?php

namespace App\Http\Controllers\Api;

use App\Models\DockingJob;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ConvertSmilesController
{
    use ApiResponseTrait;

    /**
     * Convert a SMILES string to PDBQT (standalone, no docking).
     */
    public function convert(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ligand_smiles' => 'required|string|max:2000',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation Error', 422, $validator->errors());
        }

        $conversion = $this->convertSmilesToPdbqt($request->ligand_smiles);

        if ($conversion['status'] === 'error') {
            return $this->errorResponse(
                'SMILES conversion failed: ' . $conversion['message'],
                422
            );
        }

        // Create a completed job record scoped to SMILES-only conversions
        $job = DockingJob::create([
            'user_id'      => $request->user()->id,
            'input_type'   => 'smiles',
            'smiles'       => $request->ligand_smiles,
            'protein_name' => null,
            'ligand_name'  => 'generated_from_smiles.pdbqt',
            'protein_path' => '',
            'ligand_path'  => $conversion['output_file'],
            'status'       => 'completed',
            'result_data'  => [
                'output_file' => $conversion['output_file'],
                'smiles'      => $request->ligand_smiles,
            ],
        ]);

        return $this->successResponse('SMILES converted to PDBQT successfully', [
            'job_id'       => $job->id,
            'download_url' => url('/api/convert-smiles/download/' . $job->id),
            'smiles'       => $request->ligand_smiles,
        ]);
    }

    /**
     * Download the converted PDBQT file.
     */
    public function download(Request $request, $id)
    {
        $job = DockingJob::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->where('input_type', 'smiles')
            ->whereNull('protein_name')   // ensure it's a convert-only job
            ->first();

        if (! $job || $job->status !== 'completed') {
            return $this->errorResponse('File not available or unauthorized', 404);
        }

        $filePath = $job->result_data['output_file'] ?? $job->ligand_path ?? null;

        if (! $filePath || ! file_exists($filePath)) {
            return $this->errorResponse('File not found on server', 404);
        }

        return response()->download($filePath, 'converted_ligand_' . $job->id . '.pdbqt');
    }

    /**
     * List all SMILES conversion jobs for the authenticated user.
     */
    public function history(Request $request)
    {
        $perPage = min((int) $request->query('per_page', 15), 100);

        $paginator = DockingJob::where('user_id', $request->user()->id)
            ->where('input_type', 'smiles')
            ->whereNull('protein_name')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        $items = collect($paginator->items())->map(function ($job) {
            $data = [
                'job_id'     => $job->id,
                'status'     => $job->status,
                'smiles'     => $job->smiles,
                'created_at' => $job->created_at->toIso8601String(),
            ];

            if ($job->status === 'completed' && $job->result_data) {
                $data['results'] = [
                    'download_url' => url('/api/convert-smiles/download/' . $job->id),
                ];
            }

            return $data;
        });

        return $this->successResponse('Conversion history retrieved successfully', [
            'items'      => $items,
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
     * Internal helper: run the SMILES → PDBQT Python conversion.
     *
     * @return array{status: string, output_file?: string, message?: string}
     */
    private function convertSmilesToPdbqt(string $smiles): array
    {
        $pythonPath = env('DOCKING_PYTHON_PATH', base_path('vina_env/bin/python'));
        $scriptPath = env('SMILES_SCRIPT_PATH', base_path('scripts/smiles_to_pdbqt.py'));

        $outputFilename = Str::random(40) . '.pdbqt';
        $outputDir      = storage_path('app/private/docking/generated');
        $outputPath     = $outputDir . '/' . $outputFilename;

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
            $result     = Process::timeout(120)->run($cmd);
            $fullOutput = $result->output();
            $outputData = null;

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
