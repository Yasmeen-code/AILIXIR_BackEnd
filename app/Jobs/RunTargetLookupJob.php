<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RunTargetLookupJob implements ShouldQueue
{
    use Queueable;

    public function __construct(protected \App\Models\TargetLookup $targetLookup) {}

    public function handle(\App\Services\ScreeningService $screeningService): void
    {
        $this->targetLookup->update(['status' => 'processing']);

        try {
            $input = $this->targetLookup->input ?? [];
            if (empty($input['disease_name'])) {
                throw new \Exception('Disease name not found in input');
            }

            $output = $screeningService->getTargets($input);

            if (is_array($output)) {
                unset($output['warnings']);
            }

            $this->targetLookup->update([
                'output' => $output,
                'status' => 'completed',
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Target lookup job failed', [
                'id' => $this->targetLookup->id,
                'error' => $e->getMessage()
            ]);

            $this->targetLookup->update([
                'status' => 'failed',
                'output' => ['error' => $e->getMessage()],
            ]);
        }
    }
}
