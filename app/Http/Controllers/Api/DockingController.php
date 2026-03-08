<?php

namespace App\Http\Controllers\Api;

use App\Jobs\RunDockingJob;
use App\Models\DockingJob;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class DockingController
{
    use ApiResponseTrait;

    public function submit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'protein_file' => 'nullable|file',
            'ligand_file' => 'required|file',
            'center_x' => 'required|numeric',
            'center_y' => 'required|numeric',
            'center_z' => 'required|numeric',
            'box_size_x' => 'required|numeric',
            'box_size_y' => 'required|numeric',
            'box_size_z' => 'required|numeric',
            'exhaustiveness' => 'nullable|integer',
            'n_poses' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation Error', 422, $validator->errors());
        }

        // Save files to local storage (storage/app/docking)
        // Note: Python needs direct local paths to execute
        $ligandFilename = \Illuminate\Support\Str::random(40).'.pdbqt';
        $ligandPath = $request->file('ligand_file')->storeAs('docking', $ligandFilename);
        $originalLigandName = $request->file('ligand_file')->getClientOriginalName();

        // Use uploaded protein file or fall back to default
        if ($request->hasFile('protein_file')) {
            $proteinFilename = \Illuminate\Support\Str::random(40).'.pdbqt';
            $proteinPath = $request->file('protein_file')->storeAs('docking', $proteinFilename);
            $originalProteinName = $request->file('protein_file')->getClientOriginalName();
        } else {
            $proteinPath = env('DEFAULT_PROTEIN_PATH', resource_path('docking/proteins/default_protein.pdbqt'));
            $originalProteinName = 'default_protein.pdbqt';
        }

        // Create Database Record
        $job = DockingJob::create([
            'user_id' => $request->user()->id,
            'protein_name' => $originalProteinName,
            'ligand_name' => $originalLigandName,
            'protein_path' => storage_path('app/private/'.$proteinPath),
            'ligand_path' => storage_path('app/private/'.$ligandPath),
            'status' => 'pending',
        ]);

        // Dispatch background job
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

    public function status(Request $request, $id)
    {
        $job = DockingJob::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $job) {
            return $this->errorResponse('Job not found or unauthorized', 404);
        }

        $resultData = $job->result_data;
        if ($job->status === 'completed' && isset($resultData['output_file'])) {
            $resultData['download_url'] = url('/api/docking/download/'.$job->id);
            unset($resultData['output_file']);
        }

        return $this->successResponse('Job Status Retrieved', [
            'job_id' => $job->id,
            'status' => $job->status,
            'input_info' => [
                'protein' => $job->protein_name,
                'ligand' => $job->ligand_name,
            ],
            'result_data' => $resultData,
        ]);
    }

    public function download(Request $request, $id)
    {
        $job = DockingJob::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $job || $job->status !== 'completed' || empty($job->result_data['output_file'])) {
            return $this->errorResponse('File not available or unauthorized', 404);
        }

        $filePath = $job->result_data['output_file'];

        if (! file_exists($filePath)) {
            return $this->errorResponse('File not found on server', 404);
        }

        return response()->download($filePath, 'docking_result_'.$job->id.'.pdbqt');
    }
}
