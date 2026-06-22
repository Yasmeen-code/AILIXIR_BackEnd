<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\MdSimulation\SubmitRequest;
use App\Models\MdSimulationJob;
use App\Services\MdSimulationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MdSimulationController extends BaseController
{
    private MdSimulationService $service;

    public function __construct(MdSimulationService $service)
    {
        $this->service = $service;
    }

    public function process(SubmitRequest $request): JsonResponse
    {
        $protein = $request->file('protein');
        $ligand = $request->file('ligand');

        $params = $request->safe()->except(['protein', 'ligand']);

        $response = $this->service->submitJob(
            $protein->getRealPath(),
            $protein->getClientOriginalName(),
            $ligand->getRealPath(),
            $ligand->getClientOriginalName(),
            $params
        );

        if ($response->failed()) {
            return $this->errorResponse(
                'MD Simulation service error: '.$response->body(),
                $response->status()
            );
        }

        $remoteJobId = $response->json('job_id');

        if (! $remoteJobId) {
            return $this->errorResponse('Service did not return a job ID', 500);
        }

        $job = MdSimulationJob::create([
            'user_id' => Auth::id(),
            'remote_job_id' => $remoteJobId,
            'status' => 'processing',
            'input_params' => $params,
            'protein_original_name' => $protein->getClientOriginalName(),
            'ligand_original_name' => $ligand->getClientOriginalName(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'MD Simulation job submitted successfully',
            'data' => [
                'id' => $job->id,
                'remote_job_id' => $job->remote_job_id,
                'status' => $job->status,
                'created_at' => $job->created_at->toDateTimeString(),
            ],
        ], 202);
    }

    public function status(int $id): JsonResponse
    {
        $job = MdSimulationJob::where('user_id', Auth::id())->findOrFail($id);

        $response = $this->service->getJobStatus($job->remote_job_id);

        if ($response->successful()) {
            $remoteStatus = $response->json('status');

            if ($remoteStatus) {
                $mapped = $this->mapRemoteStatus($remoteStatus);

                if ($mapped !== $job->status) {
                    $update = ['status' => $mapped];

                    if ($mapped === 'completed') {
                        $update['result_meta'] = [
                            'download_url' => $response->json('download_url'),
                            'download_analysis_url' => $response->json('download_analysis_url'),
                        ];
                    } elseif ($mapped === 'failed') {
                        $update['error_message'] = $response->json('error') ?? $remoteStatus;
                    }

                    $job->update($update);
                }
            }
        }

        return $this->successResponse('Status retrieved', [
            'id' => $job->id,
            'remote_job_id' => $job->remote_job_id,
            'status' => $job->status,
            'remote_status' => $response->json('status'),
            'protein' => $job->protein_original_name,
            'ligand' => $job->ligand_original_name,
            'result_meta' => $job->result_meta,
            'analysis_meta' => $job->analysis_meta,
            'error_message' => $job->error_message,
            'created_at' => $job->created_at->toDateTimeString(),
        ]);
    }

    public function analyze(Request $request, int $id): JsonResponse
    {
        $job = MdSimulationJob::where('user_id', Auth::id())->findOrFail($id);

        if (! $job->isCompleted()) {
            return $this->errorResponse('Job is not completed yet', 400);
        }

        $response = $this->service->runAnalysis($job->remote_job_id, $request->only([
            'rmsd_mask', 'cc_mask', 'skip', 'dpi', 'threshold',
        ]));

        if ($response->failed()) {
            return $this->errorResponse(
                'Analysis failed: '.$response->body(),
                $response->status()
            );
        }

        $data = $response->json();

        $job->update([
            'analysis_meta' => [
                'download_url' => $data['download_url'] ?? null,
                'outputs' => $data['outputs'] ?? [],
            ],
        ]);

        return $this->successResponse('Analysis triggered successfully', [
            'id' => $job->id,
            'download_url' => $data['download_url'] ?? null,
            'outputs' => $data['outputs'] ?? [],
        ]);
    }

    public function download(int $id): JsonResponse|\Illuminate\Http\Response
    {
        $job = MdSimulationJob::where('user_id', Auth::id())->findOrFail($id);

        if (! $job->isCompleted()) {
            return $this->errorResponse('Simulation not completed yet', 400);
        }

        $response = $this->service->downloadResults($job->remote_job_id);

        if ($response->failed()) {
            return $this->errorResponse('Results not available', 404);
        }

        return response($response->body(), 200)
            ->header('Content-Type', $response->header('Content-Type') ?? 'application/zip')
            ->header('Content-Disposition', "attachment; filename=\"{$job->remote_job_id}_Results.zip\"");
    }

    public function downloadAnalysis(int $id): JsonResponse|\Illuminate\Http\Response
    {
        $job = MdSimulationJob::where('user_id', Auth::id())->findOrFail($id);

        if (! $job->isCompleted()) {
            return $this->errorResponse('Simulation not completed yet', 400);
        }

        $response = $this->service->downloadAnalysis($job->remote_job_id);

        if ($response->failed()) {
            return $this->errorResponse('Analysis results not available', 404);
        }

        return response($response->body(), 200)
            ->header('Content-Type', $response->header('Content-Type') ?? 'application/zip')
            ->header('Content-Disposition', "attachment; filename=\"{$job->remote_job_id}_Analysis.zip\"");
    }

    public function history(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);

        $jobs = MdSimulationJob::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return $this->paginatedResponse('MD Simulation history retrieved', $jobs);
    }

    private function mapRemoteStatus(string $remoteStatus): string
    {
        if (str_starts_with($remoteStatus, 'Success:')) {
            return 'completed';
        }
        if (str_starts_with($remoteStatus, 'Failed:')) {
            return 'failed';
        }

        return 'processing';
    }
}
