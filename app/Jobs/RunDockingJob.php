<?php

namespace App\Jobs;

use App\Models\DockingJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class RunDockingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $dockingJob;

    public $params;

    /**
     * Create a new job instance.
     */
    public function __construct(DockingJob $dockingJob, array $params)
    {
        $this->dockingJob = $dockingJob;
        $this->params = $params;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Mark as processing
        $this->dockingJob->update(['status' => 'processing']);

        try {
            $scriptPath = env('DOCKING_SCRIPT_PATH', base_path('scripts/vina_docking.py'));
            $pythonPath = env('DOCKING_PYTHON_PATH', base_path('vina_env/bin/python'));

            // Build command
            $cmd = sprintf(
                '%s %s %s %s %f %f %f %f %f %f %d %d',
                escapeshellarg($pythonPath),
                escapeshellarg($scriptPath),
                escapeshellarg($this->dockingJob->protein_path),
                escapeshellarg($this->dockingJob->ligand_path),
                $this->params['center_x'],
                $this->params['center_y'],
                $this->params['center_z'],
                $this->params['box_size_x'],
                $this->params['box_size_y'],
                $this->params['box_size_z'],
                $this->params['exhaustiveness'] ?? 8,
                $this->params['n_poses'] ?? 5
            );

            // Run process
            $result = Process::run($cmd);

            // Using regex to reliably find our JSON object in the mixed python Vina logs output
            $outputData = null;
            $fullOutput = $result->output();

            if (preg_match('/\{"status":\s*"(success|error)".*\}/s', $fullOutput, $matches)) {
                $outputData = json_decode($matches[0], true);
            }

            // Also check standard error in case Python printed an error payload there
            if (! $outputData && preg_match('/\{"status":\s*"(success|error)".*\}/s', $result->errorOutput(), $matches)) {
                $outputData = json_decode($matches[0], true);
            }

            if (! $result->successful()) {
                $errorMessage = $outputData['message'] ?? $result->errorOutput();
                if (empty(trim($errorMessage))) {
                    $errorMessage = trim($fullOutput) ?: 'Unknown Error / Empty Output';
                }
                throw new \Exception('Process Failed: '.$errorMessage);
            }

            if (isset($outputData['status']) && $outputData['status'] === 'error') {
                throw new \Exception('Python Error: '.($outputData['message'] ?? 'Unknown script error'));
            }

            $this->dockingJob->update([
                'status' => 'completed',
                'result_data' => $outputData ?: ['raw_output' => $fullOutput], // fallback stores raw payload
            ]);

        } catch (\Exception $e) {
            Log::error('Docking Job Failed', ['error' => $e->getMessage(), 'job_id' => $this->dockingJob->id]);
            $this->dockingJob->update([
                'status' => 'failed',
                'result_data' => ['error' => $e->getMessage()],
            ]);
        }
    }
}
