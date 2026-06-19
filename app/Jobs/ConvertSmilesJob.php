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
use Illuminate\Support\Str;

class ConvertSmilesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;

    public function __construct(
        public DockingJob $dockingJob,
        public string $smiles,
        public array $params,
    ) {}

    public function handle(): void
    {
        $pythonPath = env('DOCKING_PYTHON_PATH', base_path('vina_env/bin/python'));
        $scriptPath = env('SMILES_SCRIPT_PATH', base_path('scripts/smiles_to_pdbqt.py'));

        $outputFilename = Str::random(40).'.pdbqt';
        $outputDir = storage_path('app/private/docking/generated');
        $outputPath = $outputDir.'/'.$outputFilename;

        $cmd = sprintf(
            '%s %s %s %s',
            escapeshellarg($pythonPath),
            escapeshellarg($scriptPath),
            escapeshellarg($this->smiles),
            escapeshellarg($outputPath)
        );

        try {
            $result = Process::timeout($this->timeout)->run($cmd);

            $outputData = null;
            $fullOutput = $result->output();

            if (preg_match('/\{"status":\s*"(success|error)".*\}/s', $fullOutput, $matches)) {
                $outputData = json_decode($matches[0], true);
            }

            if (! $result->successful() || ($outputData && $outputData['status'] === 'error')) {
                $errorMsg = $outputData['message'] ?? $result->errorOutput() ?: 'Unknown conversion error';
                throw new \Exception('SMILES conversion failed: '.$errorMsg);
            }

            $this->dockingJob->update([
                'ligand_path' => $outputData['output_file'] ?? $outputPath,
            ]);

            RunDockingJob::dispatch($this->dockingJob, $this->params);

        } catch (\Exception $e) {
            Log::error('SMILES conversion failed', [
                'job_id' => $this->dockingJob->id,
                'smiles' => $this->smiles,
                'error' => $e->getMessage(),
            ]);

            $this->dockingJob->update([
                'status' => 'failed',
                'result_data' => ['error' => $e->getMessage()],
            ]);
        }
    }
}
