<?php

namespace App\Jobs;

use App\Models\Simulation;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

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

        if (!file_exists($workDir)) mkdir($workDir, 0777, true);

        $protein = storage_path("app/" . $this->simulation->protein);
        $ligand  = storage_path("app/" . $this->simulation->ligand);

        try {

            // Docking
            exec("python scripts/docking_openmm.py $workDir $protein $ligand");
            $this->simulation->update(['progress' => 30]);

            // MD Simulation
            exec("python scripts/md_openmm.py $workDir $protein $ligand");
            $this->simulation->update(['progress' => 60]);

            // Analysis
            exec("python scripts/analysis_openmm.py $workDir");
            $this->simulation->update(['progress' => 90]);

            // Rendering video
            exec("python scripts/render_video.py $workDir");
            $this->simulation->update(['progress' => 100, 'status' => 'completed']);

            $analysisData = json_decode(file_get_contents("$workDir/analysis.json"), true);

            // Finish
            $this->simulation->update([
                'status' => 'completed',
                'progress' => 100,
                'trajectory' => "simulations/$id/trajectory.dcd",
                'video' => "simulations/$id/video.mp4",
                'analysis' => $analysisData
            ]);
        } catch (\Exception $e) {
            $this->simulation->update(['status' => 'failed']);
        }
    }
}
