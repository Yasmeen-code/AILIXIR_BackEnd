<?php

namespace App\Jobs;

use App\Models\Simulation;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Symfony\Component\Process\Process;

class RunPipelineJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    protected $simulation;

    public function __construct(Simulation $simulation)
    {
        $this->simulation = $simulation;
    }

    public function handle()
    {
        $id = $this->simulation->id;
        $workDir = storage_path("app/simulations/$id");

        if (!file_exists($workDir)) {
            mkdir($workDir, 0777, true);
        }

        try {
            $proteinPath = storage_path("app/" . $this->simulation->protein);
            $ligandPath  = storage_path("app/" . $this->simulation->ligand);

            copy($proteinPath, "$workDir/protein.pdb");
            copy($ligandPath, "$workDir/ligand.mol2");

            // 1. MD
            $this->runProcess(["python", "scripts/md.py", $workDir, "$workDir/protein.pdb", "$workDir/ligand.mol2"]);
            $this->simulation->update(['progress' => 50]);

            // 2. Analysis
            $this->runProcess(["python", "scripts/analysis.py", $workDir]);
            $this->simulation->update(['progress' => 90]);

            $analysis = json_decode(file_get_contents("$workDir/analysis.json"), true);

            $this->simulation->update([
                'status' => 'completed',
                'progress' => 100,
                'analysis' => $analysis,
                'trajectory' => "simulations/$id/trajectory.dcd"
            ]);
        } catch (\Exception $e) {
            $this->simulation->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);
        }
    }

    private function runProcess(array $command)
    {
        $process = new Process($command);
        $process->setTimeout(3600);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \Exception($process->getErrorOutput());
        }
    }
}
