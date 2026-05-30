<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Jobs\RunDockingJob;
use App\Models\DockingJob;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class DockingTestController extends Controller
{
    use ApiResponseTrait;

    public function testDockingSubmit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'protein_name' => 'required|string|max:255',
            'ligand_name'  => 'nullable|string|max:255',
            'ligand_file'  => 'nullable|file|required_without:ligand_smiles',
            'ligand_smiles' => 'nullable|string|max:2000|required_without:ligand_file',
            'protein_file' => 'required|file',
            'center_x' => 'required|numeric',
            'center_y' => 'required|numeric',
            'center_z' => 'required|numeric',
            'box_size_x' => 'required|numeric',
            'box_size_y' => 'required|numeric',
            'box_size_z' => 'required|numeric',
            'exhaustiveness' => 'nullable|integer',
            'n_poses' => 'nullable|integer',
        ]);

        $validator->after(function ($validator) use ($request) {
            if ($request->hasFile('ligand_file') && $request->filled('ligand_smiles')) {
                $validator->errors()->add(
                    'ligand_input',
                    'Provide either a ligand file or a SMILES string, not both.'
                );
            }
        });

        if ($validator->fails()) {
            return $this->errorResponse('Validation Error', 422, $validator->errors());
        }

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
