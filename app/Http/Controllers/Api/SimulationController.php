<?php

namespace App\Http\Controllers\Api;

use App\Models\Simulation;
use App\Jobs\RunMolecularDynamicsJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SimulationController extends BaseController
{
    public function run(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'protein' => 'required|file|max:20480',
            'ligand' => 'nullable|file|max:10240',
            'force_field' => 'nullable|in:ff19SB,ff14SB',
            'temperature' => 'nullable|numeric|min:200|max:400',
            'simulation_time_ns' => 'nullable|numeric|min:1|max:500',
            'box_size' => 'nullable|integer|min:8|max:25',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $simulationId = uniqid('sim_');

        $simulationDir = storage_path("app/simulations/{$simulationId}");
        if (!is_dir($simulationDir)) {
            mkdir($simulationDir, 0777, true);
        }


        $proteinFile = $request->file('protein');
        $proteinPath = "simulations/{$simulationId}/protein.pdb";
        $proteinFile->move($simulationDir, 'protein.pdb');

        $ligandPath = null;
        if ($request->hasFile('ligand')) {
            $ligandFile = $request->file('ligand');
            $ligandPath = "simulations/{$simulationId}/ligand.pdb";
            $ligandFile->move($simulationDir, 'ligand.pdb');
        }

        $simulation = Simulation::create([
            'user_id' => Auth::id(),
            'protein' => $proteinPath,
            'ligand' => $ligandPath,
            'status' => 'pending',
            'progress' => 0,
            'force_field' => $request->input('force_field', 'ff14SB'),
            'temperature' => $request->input('temperature', 298),
            'simulation_time_ns' => $request->input('simulation_time_ns', 10),
            'box_size' => $request->input('box_size', 12),
        ]);

        RunMolecularDynamicsJob::dispatch($simulation);

        return response()->json([
            'success' => true,
            'message' => 'Simulation started',
            'data' => ['simulation_id' => $simulation->id, 'status' => $simulation->status]
        ], 201);
    }

    public function status($id)
    {
        $simulation = Simulation::where('user_id', Auth::id())->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $simulation->id,
                'status' => $simulation->status,
                'progress' => $simulation->progress,
                'error_message' => $simulation->error_message,
                'created_at' => $simulation->created_at,
                'results' => $simulation->isCompleted() ? $simulation->analysis : null,
            ]
        ]);
    }

    public function index(Request $request)
    {
        $simulations = Simulation::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json(['success' => true, 'data' => $simulations->items()]);
    }

    public function destroy($id)
    {
        $simulation = Simulation::where('user_id', Auth::id())->findOrFail($id);

        if ($simulation->protein && Storage::exists($simulation->protein)) {
            Storage::delete($simulation->protein);
        }
        if ($simulation->ligand && Storage::exists($simulation->ligand)) {
            Storage::delete($simulation->ligand);
        }

        $simDir = dirname($simulation->protein);
        if (Storage::exists($simDir)) {
            Storage::deleteDirectory($simDir);
        }

        $simulation->delete();

        return response()->json(['success' => true, 'message' => 'Deleted']);
    }
}
