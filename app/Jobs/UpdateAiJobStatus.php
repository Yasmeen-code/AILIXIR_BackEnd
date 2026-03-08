<?php


namespace App\Jobs;

use App\Services\AiService;
use App\Models\AiJob;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateAiJobStatus implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected AiJob $aiJob;

    public function __construct(AiJob $aiJob)
    {
        $this->aiJob = $aiJob;
    }

    public function handle(AiService $aiService)
    {
        $statusResponse = $aiService->status($this->aiJob->job_id);
        $previewResponse = $aiService->preview($this->aiJob->job_id);

        $this->aiJob->update([
            'status' => $statusResponse['status'] ?? $this->aiJob->status,
            'preview' => $previewResponse ?? $this->aiJob->preview
        ]);

        if (($statusResponse['status'] ?? '') === 'running') {
            self::dispatch($this->aiJob)->delay(now()->addSeconds(30));
        }
    }
}
