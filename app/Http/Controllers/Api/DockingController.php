<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Docking\SubmitDockingRequest;
use App\Jobs\ConvertSmilesJob;
use App\Jobs\RunDockingJob;
use App\Models\DockingJob;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DockingController
{
    use ApiResponseTrait;

    public function submit(SubmitDockingRequest $request)
    {
        $isSmiles = $request->filled('ligand_smiles');

        if ($isSmiles) {
            $ligandPath = null;
        } else {
            $ligandPath = $this->storeAsPdbqt($request->file('ligand_file'));
        }

        $proteinPath = $this->storeAsPdbqt($request->file('protein_file'));

        $job = DockingJob::create([
            'user_id' => $request->user()->id,
            'input_type' => $isSmiles ? 'smiles' : 'file',
            'smiles' => $isSmiles ? $request->ligand_smiles : null,
            'protein_name' => $request->protein_name,
            'ligand_name' => $request->ligand_name,
            'protein_path' => $proteinPath,
            'ligand_path' => $ligandPath,
            'status' => 'pending',
        ]);

        $params = [
            'center_x' => $request->center_x,
            'center_y' => $request->center_y,
            'center_z' => $request->center_z,
            'box_size_x' => $request->box_size_x,
            'box_size_y' => $request->box_size_y,
            'box_size_z' => $request->box_size_z,
            'exhaustiveness' => $request->exhaustiveness ?? 8,
            'n_poses' => $request->n_poses ?? 5,
        ];

        if ($isSmiles) {
            ConvertSmilesJob::dispatch($job, $request->ligand_smiles, $params);
        } else {
            RunDockingJob::dispatch($job, $params);
        }

        return $this->successResponse('Docking Job Successfully Queued', [
            'job_id' => $job->id,
            'status' => $job->status,
        ]);
    }

    public function history(Request $request)
    {
        $perPage = min((int) $request->query('per_page', 15), 100);

        $paginator = DockingJob::dockingOnly()
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        $items = collect($paginator->items())->map(function ($job) {
            return [
                'id' => $job->id,
                'status' => $job->status,
                'protein' => $job->protein_name,
                'ligand' => $job->ligand_name,
                'created_at' => $job->created_at->toIso8601String(),
                'download_url' => url('/api/docking/download/'.$job->id),
                'scores' => $job->vina_scores ?? [],
            ];
        });

        return response()->json([
            'results' => $items,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'has_more' => $paginator->hasMorePages(),
            ],
        ]);
    }

    public function status(Request $request, $id)
    {
        $job = DockingJob::dockingOnly()
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $job) {
            return $this->errorResponse('Docking job not found or unauthorized', 404);
        }

        $data = [
            'id' => $job->id,
            'status' => $job->status,
            'protein' => $job->protein_name,
            'ligand' => $job->ligand_name,
            'created_at' => $job->created_at->toIso8601String(),
            'download_url' => url('/api/docking/download/'.$job->id),
            'scores' => $job->vina_scores ?? [],
        ];

        return response()->json(array_merge(
            ['success' => true, 'message' => 'Job details retrieved successfully'],
            $data
        ));
    }

    public function download(Request $request, $id)
    {
        $token = $request->bearerToken() ?? $request->query('token');

        if (!$token) {
            return $this->errorResponse('Unauthenticated', 401);
        }

        $accessToken = PersonalAccessToken::findToken($token);
        if (!$accessToken || !$accessToken->tokenable) {
            return $this->errorResponse('Invalid token', 401);
        }

        $job = DockingJob::dockingOnly()
            ->where('id', $id)
            ->where('user_id', $accessToken->tokenable->id)
            ->first();

        if (! $job || $job->status !== 'completed') {
            return $this->errorResponse('Docking file not available or unauthorized', 404);
        }

        $filePath = $job->result_data['output_file'] ?? null;

        if (! $filePath || ! file_exists($filePath)) {
            return $this->errorResponse('File not found on server', 404);
        }

        return new StreamedResponse(function () use ($filePath) {
            $stream = fopen($filePath, 'rb');
            if ($stream) {
                fpassthru($stream);
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="docking_result_'.$job->id.'.pdbqt"',
            'Content-Length' => filesize($filePath),
        ]);
    }

    private function storeAsPdbqt(UploadedFile $file): string
    {
        $ext = strtolower($file->getClientOriginalExtension());
        $filename = Str::random(40).'.pdbqt';
        $destPath = storage_path('app/private/docking/'.$filename);

        if ($ext === 'pdbqt') {
            $file->storeAs('docking', $filename);
        } elseif ($ext === 'pdb') {
            $this->convertPdbToPdbqt($file->getRealPath(), $destPath);
        } else {
            abort(422, 'Unsupported file format: .'.$ext.'. Only .pdb and .pdbqt files are accepted.');
        }

        return $destPath;
    }

    private function convertPdbToPdbqt(string $source, string $dest): void
    {
        $pythonPath = env('DOCKING_PYTHON_PATH');
        $obabel = $pythonPath ? dirname($pythonPath).'/obabel' : 'obabel';
        if (! file_exists($obabel)) {
            $obabel = 'obabel';
        }

        $result = Process::timeout(60)->run([
            $obabel,
            '-ipdb', $source,
            '-opdbqt',
            '-O', $dest,
        ]);

        if (! $result->successful()) {
            Log::error('PDB-to-PDBQT conversion failed', [
                'source' => $source,
                'dest'   => $dest,
                'error'  => $result->errorOutput(),
            ]);
            abort(500, 'Failed to convert PDB file to PDBQT format.');
        }
    }
}
