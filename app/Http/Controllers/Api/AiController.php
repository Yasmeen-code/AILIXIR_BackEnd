<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Ai\RunAiRequest;
use App\Services\AiService;
use App\Models\AiJob;
use App\Jobs\UpdateAiJobStatus;
use Illuminate\Support\Facades\Auth;

class AiController extends BaseController
{
    public function run(RunAiRequest $request, AiService $aiService)
    {
        $data = $request->validated();

        $response = $aiService->run($data);

        $job = AiJob::create([
            'user_id' => Auth::id(),
            'job_id' => $response['job_id'],
            'status' => $response['status'] ?? 'running',
            'parameters' => $data,
            'preview' => []
        ]);

        UpdateAiJobStatus::dispatch($job)->delay(now()->addSeconds(5));

        return response()->json($job);
    }

    public function status(AiJob $job)
    {
        return response()->json($job);
    }

    public function preview(AiJob $job)
    {
        return response()->json($job->preview);
    }

    public function downloadTop(AiJob $job, AiService $aiService)
    {
        $response = $aiService->downloadTop($job->job_id);
        return response($response->body(), 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="top_results.csv"');
    }

    public function downloadFull(AiJob $job, AiService $aiService)
    {
        $response = $aiService->downloadFull($job->job_id);
        return response($response->body(), 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="full_results.csv"');
    }

    public function history()
    {
        return AiJob::where('user_id', Auth::id())->latest()->paginate(10);
    }
}
