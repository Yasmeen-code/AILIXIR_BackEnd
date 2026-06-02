<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\AiJob;
use App\Models\LigandExport;
use Illuminate\Support\Facades\Auth;

class CheckJobOwnership
{
    public function handle(Request $request, Closure $next)
    {
        $jobId = $request->route('job_id');

        if (!$jobId) {
            return response()->json([
                'success' => false,
                'message' => 'Job ID is required'
            ], 400);
        }

        $aiJob = AiJob::where('job_id', $jobId)
            ->where('user_id', Auth::id())
            ->first();

        $ligandExport = LigandExport::where('job_id', $jobId)
            ->where('user_id', Auth::id())
            ->first();

        if (!$aiJob && !$ligandExport) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to access this job'
            ], 403);
        }

        $request->merge(['ai_job' => $aiJob]);
        $request->merge(['ligand_export' => $ligandExport]);

        return $next($request);
    }
}
