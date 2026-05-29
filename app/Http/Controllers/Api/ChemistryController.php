<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChemistryChatRequest;
use App\Http\Requests\ChemistryCompareRequest;
use App\Http\Requests\ChemistryDockingRequest;
use App\Http\Resources\ChemistryResponseResource;
use App\Models\ChemistryAnalysis;
use App\Models\ChemistryCsvJob;
use App\Models\ChemistryThread;
use App\Services\ChemistryApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;


class ChemistryController extends BaseController
{
    public function __construct(
        protected ChemistryApiService $chemistryService
    ) {}

    // ==================== Health ====================
    public function health(): JsonResponse
    {
        $result = $this->chemistryService->healthCheck();
        return response()->json(new ChemistryResponseResource($result));
    }

    // ==================== Threads ====================
    public function createThread(): JsonResponse
    {
        $user = Auth::user();

        $result = $this->chemistryService->createThread();

        if (!$result['success']) {
            return response()->json(new ChemistryResponseResource($result), 500);
        }

        $thread = ChemistryThread::create([
            'user_id' => $user->id,
            'thread_id' => $result['data']['thread_id'],
            'title' => 'New Conversation',
            'status' => 'active',
            'last_used_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'thread_id' => $thread->thread_id,
                'id' => $thread->id,
                'created_at' => $thread->created_at,
            ],
        ]);
    }

    public function listThreads(): JsonResponse
    {
        $threads = Auth::user()->chemistryThreads()
            ->where('status', 'active')
            ->orderBy('last_used_at', 'desc')
            ->get(['id', 'thread_id', 'title', 'last_used_at', 'created_at']);

        return response()->json([
            'success' => true,
            'data' => $threads,
        ]);
    }

    // ==================== Chat ====================
    public function chat(ChemistryChatRequest $request): JsonResponse
    {
        $user = Auth::user();
        $threadId = $request->input('thread_id');

        if ($threadId) {
            $thread = ChemistryThread::where('user_id', $user->id)
                ->where('thread_id', $threadId)
                ->first();

            if (!$thread) {
                return response()->json([
                    'success' => false,
                    'error' => 'Thread not found or access denied',
                ], 403);
            }

            $thread->update(['last_used_at' => now()]);
        }

        $result = $this->chemistryService->chat(
            $request->input('message'),
            $threadId
        );

        if (!$result['success']) {
            return response()->json(new ChemistryResponseResource($result), $result['status']);
        }

        ChemistryAnalysis::create([
            'user_id' => $user->id,
            'chemistry_thread_id' => $thread?->id,
            'type' => 'chat',
            'input_data' => $request->input('message'),
            'response' => $result['data']['reply'] ?? null,
            'processing_time_ms' => $result['data']['processing_time_ms'] ?? null,
            'status' => 'success',
        ]);

        return response()->json(new ChemistryResponseResource($result));
    }

    // ==================== Analyze SMILES ====================
    public function analyzeSmiles(Request $request): JsonResponse
    {
        $user = auth()->user();

        $request->validate([
            'smiles' => 'required|string|max:1000',
            'thread_id' => 'nullable|string|max:255',
        ]);

        $threadId = $request->input('thread_id');
        $thread = $this->validateThread($user->id, $threadId);

        $smiles = $request->input('smiles');

        $result = $this->chemistryService->analyzeSmiles($smiles, $threadId);

        return $this->saveAndRespond($result, $user->id, 'smiles', $smiles, $thread?->id);
    }

    // ==================== Compare ====================
    public function compare(ChemistryCompareRequest $request): JsonResponse
    {
        $user = Auth::user();
        $threadId = $request->input('thread_id');
        $thread = $this->validateThread($user->id, $threadId);

        $result = $this->chemistryService->compareMolecules(
            $request->input('smiles'),
            $threadId
        );

        $input = implode(', ', $request->input('smiles'));
        return $this->saveAndRespond($result, $user->id, 'compare', $input, $thread?->id);
    }

    // ==================== Docking ====================
    public function docking(ChemistryDockingRequest $request): JsonResponse
    {
        $user = Auth::user();
        $threadId = $request->input('thread_id');
        $thread = $this->validateThread($user->id, $threadId);

        $result = $this->chemistryService->analyzeDocking(
            $request->input('docking_data'),
            $threadId
        );

        return $this->saveAndRespond($result, $user->id, 'docking', $request->input('docking_data'), $thread?->id);
    }

    // ==================== CSV ====================
    public function uploadCsv(Request $request): JsonResponse
    {
        $user = Auth::user();

        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
            'analysis_type' => 'nullable|in:full,quick,admet,classify',
        ]);

        $file = $request->file('file');
        $analysisType = $request->input('analysis_type', 'full');

        $result = $this->chemistryService->uploadCsv($file, $analysisType);

        if (!$result['success']) {
            return response()->json(new ChemistryResponseResource($result), $result['status']);
        }

        $apiJobId = $result['data']['job_id'] ?? null;
        $totalRows = $result['data']['total_rows'] ?? 0;

        if (!$apiJobId) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get job ID from API',
            ], 500);
        }

        $job = ChemistryCsvJob::create([
            'user_id' => $user->id,
            'job_id' => $apiJobId,
            'filename' => $file->getClientOriginalName(),
            'analysis_type' => $analysisType,
            'total_rows' => $totalRows,
            'status' => 'queued',
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'job_id' => $job->job_id,
                'id' => $job->id,
                'status' => $job->status,
                'total_rows' => $job->total_rows,
            ],
        ]);
    }

    public function csvStatus(string $jobId): JsonResponse
    {
        $user = Auth::user();

        $job = ChemistryCsvJob::where('user_id', $user->id)
            ->where('job_id', $jobId)
            ->first();

        if (!$job) {
            return response()->json([
                'success' => false,
                'error' => 'Job not found or access denied',
            ], 404);
        }

        $result = $this->chemistryService->getCsvStatus($jobId);

        if ($result['success'] && isset($result['data'])) {
            $apiData = $result['data'];

            $job->update([
                'status' => $apiData['status'] ?? $job->status,
                'completed_rows' => $apiData['completed'] ?? $job->completed_rows,
                'failed_rows' => $apiData['failed_rows'] ?? $job->failed_rows,
                'progress_percent' => $apiData['progress_percent'] ?? $job->progress_percent,
            ]);

            if ($apiData['status'] === 'done' && !$job->completed_at) {
                $job->update(['completed_at' => now(), 'status' => 'done']);
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'job_id' => $job->job_id,
                'status' => $job->status,
                'total' => $job->total_rows,
                'completed' => $job->completed_rows,
                'failed' => $job->failed_rows,
                'progress_percent' => $job->progress_percent,
            ],
        ]);
    }

    public function csvResults(string $jobId)
    {
        $user = Auth::user();

        $job = ChemistryCsvJob::where('user_id', $user->id)
            ->where('job_id', $jobId)
            ->first();

        if (!$job) {
            return response()->json([
                'success' => false,
                'error' => 'Job not found or access denied',
            ], 404);
        }

        if ($job->status !== 'done') {
            return response()->json([
                'success' => false,
                'error' => 'Job not completed yet',
            ], 400);
        }

        if ($job->result_file_path && Storage::exists($job->result_file_path)) {
            return Storage::download($job->result_file_path);
        }

        $result = $this->chemistryService->getCsvResults($jobId);

        if (is_string($result)) {
            $path = "chemistry_results/{$jobId}.csv";
            Storage::put($path, $result);
            $job->update(['result_file_path' => $path]);

            return response($result)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="results_' . $jobId . '.csv"');
        }

        return response()->json(new ChemistryResponseResource($result));
    }

    public function listJobs(): JsonResponse
    {
        $jobs = Auth::user()->chemistryCsvJobs()
            ->orderBy('created_at', 'desc')
            ->get([
                'id',
                'job_id',
                'filename',
                'analysis_type',
                'status',
                'total_rows',
                'completed_rows',
                'failed_rows',
                'progress_percent',
                'created_at'
            ]);

        return response()->json([
            'success' => true,
            'data' => $jobs,
        ]);
    }

    public function deleteJob(string $jobId): JsonResponse
    {
        $user = Auth::user();

        $job = ChemistryCsvJob::where('user_id', $user->id)
            ->where('job_id', $jobId)
            ->first();

        if (!$job) {
            return response()->json([
                'success' => false,
                'error' => 'Job not found or access denied',
            ], 404);
        }

        $result = $this->chemistryService->deleteCsvJob($jobId);

        if ($job->result_file_path && Storage::exists($job->result_file_path)) {
            Storage::delete($job->result_file_path);
        }

        $job->delete();

        return response()->json([
            'success' => true,
            'message' => 'Job deleted successfully',
        ]);
    }

    // ==================== User History ====================
    public function userHistory(Request $request): JsonResponse
    {
        $user = Auth::user();
        $type = $request->input('type');

        $query = $user->chemistryAnalyses()
            ->with('thread:id,thread_id,title')
            ->orderBy('created_at', 'desc');

        if ($type) {
            $query->where('type', $type);
        }

        $history = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

    // ==================== Helper Methods ====================
    private function validateThread(int $userId, ?string $threadId): ?ChemistryThread
    {
        if (!$threadId) return null;

        return ChemistryThread::where('user_id', $userId)
            ->where('thread_id', $threadId)
            ->first();
    }

    private function saveAndRespond(array $result, int $userId, string $type, string $input, ?int $threadId = null): JsonResponse
    {
        if (!$result['success']) {
            ChemistryAnalysis::create([
                'user_id' => $userId,
                'chemistry_thread_id' => $threadId,
                'type' => $type,
                'input_data' => $input,
                'status' => 'failed',
                'error_message' => is_string($result['error']) ? $result['error'] : json_encode($result['error']),
            ]);

            return response()->json(new ChemistryResponseResource($result), $result['status']);
        }

        ChemistryAnalysis::create([
            'user_id' => $userId,
            'chemistry_thread_id' => $threadId,
            'type' => $type,
            'input_data' => $input,
            'response' => $result['data']['reply'] ?? null,
            'processing_time_ms' => $result['data']['processing_time_ms'] ?? null,
            'status' => 'success',
        ]);

        return response()->json(new ChemistryResponseResource($result));
    }
}
