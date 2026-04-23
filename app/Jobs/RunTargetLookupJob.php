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
            $diseaseName = $this->targetLookup->input['disease_name'] ?? null;
            if (!$diseaseName) {
                throw new \Exception('Disease name not found in input');
            }

            $output = $screeningService->getTargets($diseaseName);

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
