<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RunScreeningJob implements ShouldQueue
{
    use Queueable;

    public function __construct(protected \App\Models\ScreeningResult $screeningResult) {}

    public function handle(\App\Services\ScreeningService $screeningService): void
    {
        $this->screeningResult->update(['status' => 'processing']);

        try {
            $input = $this->screeningResult->input;
            $output = $screeningService->screen($input);

            if (is_array($output)) {
                unset($output['warnings']);
            }

            $this->screeningResult->update([
                'output' => $output,
                'status' => 'completed',
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Screening job failed', [
                'id' => $this->screeningResult->id,
                'error' => $e->getMessage()
            ]);

            $this->screeningResult->update([
                'status' => 'failed',
                'output' => ['error' => $e->getMessage()],
            ]);
        }
    }
}
