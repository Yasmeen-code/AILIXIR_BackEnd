<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Simulation;
use App\Jobs\RunPipelineJob;
use Illuminate\Support\Facades\Auth;

class SimulationController extends BaseController
{
    public function run(Request $request)
    {
        $request->validate([
            'protein' => 'required|file',
            'ligand'  => 'required|file'
        ]);

        $simulationDir = "simulations/" . uniqid();
        $protein = $request->file('protein')->store($simulationDir);
        $ligand  = $request->file('ligand')->store($simulationDir);

        $simulation = Simulation::create([
            'user_id' => Auth::id(),
            'protein' => $protein,
            'ligand' => $ligand,
            'status' => 'pending',
            'progress' => 0
        ]);

        RunPipelineJob::dispatch($simulation);

        return response()->json(["simulation_id" => $simulation->id]);
    }

    public function status($id)
    {
        $simulation = Simulation::where('user_id', Auth::id())->findOrFail($id);

        return response()->json([
            "status" => $simulation->status,
            "progress" => $simulation->progress,
            "analysis" => $simulation->analysis,
            "trajectory" => $simulation->trajectory,
            "error_message" => $simulation->error_message
        ]);
    }
}
