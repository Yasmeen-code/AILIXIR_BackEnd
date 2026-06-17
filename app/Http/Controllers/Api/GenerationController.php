<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\AiJob;
use App\Services\AiServiceClient;
use App\Http\Requests\Ai\GenerationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class GenerationController extends BaseController
{
    private AiServiceClient $aiServiceClient;

    public function __construct(AiServiceClient $aiServiceClient)
    {
        $this->aiServiceClient = $aiServiceClient;
    }

    /**
     * Run a new generation job
     */
    public function run(GenerationRequest $request): JsonResponse
    {
        $validated = $this->prepareDockingParameters($request->validated());

        $response = $this->aiServiceClient->startGeneration($validated);

        if ($response->failed()) {
            return $this->aiServiceErrorResponse($response);
        }

        $jobId = $response->json('job_id');

        if (!$jobId) {
            return $this->missingJobIdResponse();
        }

        $aiJob = $this->createAiJobRecord($jobId, $validated);

        return $this->jobStartedResponse($aiJob);
    }

    /**
     * Get job status
     */
    public function status(Request $request, string $jobId): JsonResponse
    {
        $aiJob = $this->getAiJobFromRequest($request, $jobId);

        $this->syncJobStatusFromService($aiJob);

        return $this->jobStatusResponse($aiJob);
    }

    /**
     * Get job results
     */
    public function results(Request $request, string $jobId): JsonResponse
    {
        $aiJob = $this->getAiJobFromRequest($request, $jobId);

        $response = $this->aiServiceClient->fetchResults($jobId);

        if ($response->failed()) {
            return $this->handleResultsFailure($response, $aiJob);
        }

        $this->updateAiJobWithResults($aiJob, $response->json());

        return $this->jobResultsResponse($aiJob, $response->json('results', []));
    }

    /**
     * Download a specific file (Proxy to AI service)
     */
    public function downloadFile(Request $request, string $jobId, string $filename): Response|JsonResponse
    {
        $safeFilename = basename($filename);

        $response = $this->aiServiceClient->downloadFile($jobId, $safeFilename);

        if ($response->failed()) {
            return response()->json([
                'success' => false,
                'message' => 'File not found'
            ], 404);
        }

        return $this->fileDownloadResponse($response, $safeFilename);
    }

    /**
     *  History of jobs
     */
    public function history(Request $request): JsonResponse
    {
        return $this->jobHistoryResponse($request);
    }

    // ========== Private Helper Methods ==========

    private function prepareDockingParameters(array $validated): array
    {
        if ($validated['docking_mode'] === 'all') {
            $validated['dock_top_k'] = $validated['return_top_k'];
        }

        $validated['preset'] = $validated['preset'] ?? 'egfr_generator';

        return $validated;
    }

    private function createAiJobRecord(string $jobId, array $params): AiJob
    {
        return AiJob::create([
            'user_id' => Auth::id(),
            'job_id' => $jobId,
            'status' => 'running',
            'preset' => $params['preset'],
            'num_molecules' => $params['num_molecules'],
            'return_top_k' => $params['return_top_k'],
            'docking_mode' => $params['docking_mode'],
            'dock_top_k' => $params['dock_top_k'] ?? 0,
        ]);
    }

    private function getAiJobFromRequest(Request $request, string $jobId): AiJob
    {
        return $request->get('ai_job');
    }

    private function syncJobStatusFromService(AiJob $aiJob): void
    {
        $response = $this->aiServiceClient->getJobStatus($aiJob->job_id);

        if ($response->successful()) {
            $newStatus = $response->json('status');
            if ($newStatus && $newStatus !== $aiJob->status) {
                $aiJob->update(['status' => $newStatus]);
            }
        }
    }

    private function updateAiJobWithResults(AiJob $aiJob, array $data): void
    {
        $aiJob->update([
            'status' => $data['status'] ?? 'completed',
            'summary' => $data['summary'] ?? null,
            'files' => $data['files'] ?? null,
            'ligands' => $data['results'] ?? null,
        ]);
    }

    // ========== Response Builders ==========

    private function aiServiceErrorResponse($response): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'AI service error: ' . $response->body(),
        ], $response->status());
    }

    private function missingJobIdResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'AI service did not return a job ID'
        ], 500);
    }

    private function jobStartedResponse(AiJob $aiJob): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Generation job started successfully',
            'job_id' => $aiJob->job_id,
            'status' => $aiJob->status,
            'preset' => $aiJob->preset,
            'num_molecules' => $aiJob->num_molecules,
            'return_top_k' => $aiJob->return_top_k,
            'docking_mode' => $aiJob->docking_mode,
            'dock_top_k' => $aiJob->dock_top_k,
            'created_at' => $aiJob->created_at->toDateTimeString(),
        ], 202);
    }

    private function jobStatusResponse(AiJob $aiJob): JsonResponse
    {
        return response()->json([
            'success' => true,
            'job_id' => $aiJob->job_id,
            'status' => $aiJob->status,
            'preset' => $aiJob->preset,
            'num_molecules' => $aiJob->num_molecules,
            'return_top_k' => $aiJob->return_top_k,
            'docking_mode' => $aiJob->docking_mode,
            'dock_top_k' => $aiJob->dock_top_k,
            'created_at' => $aiJob->created_at->toDateTimeString(),
        ]);
    }

    private function handleResultsFailure($response, AiJob $aiJob): JsonResponse
    {
        if ($response->status() === 404) {
            return response()->json([
                'success' => false,
                'message' => 'Results not ready yet',
                'status' => $aiJob->status
            ], 202);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch results',
        ], $response->status());
    }

    private function jobResultsResponse(AiJob $aiJob, array $ligands): JsonResponse
    {
        return response()->json([
            'success' => true,
            'job_id' => $aiJob->job_id,
            'status' => $aiJob->status,
            'preset' => $aiJob->preset,
            'num_molecules' => $aiJob->num_molecules,
            'return_top_k' => $aiJob->return_top_k,
            'docking_mode' => $aiJob->docking_mode,
            'dock_top_k' => $aiJob->dock_top_k,
            'summary' => $aiJob->getSummaryStats(),
            'files' => $this->buildFileList($aiJob->files ?? []),
            'ligands' => $ligands,
            'created_at' => $aiJob->created_at->toDateTimeString(),
        ]);
    }

    private function buildFileList(array $files): array
    {
        $fileList = [];

        foreach ($files as $type => $fileInfo) {
            $fileList[$type] = [
                'filename' => $fileInfo['filename'] ?? "{$type}_results.csv"
            ];
        }

        return $fileList;
    }

    private function fileDownloadResponse($response, string $filename): Response
    {
        $contentType = $response->header('Content-Type') ?? 'application/octet-stream';

        return response($response->body(), 200)
            ->header('Content-Type', $contentType)
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    private function jobHistoryResponse(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $aiJobs = AiJob::query()
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return $this->paginatedResponse('Generation job history retrieved successfully',  $aiJobs);
    }
}
