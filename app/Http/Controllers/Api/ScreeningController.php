<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Screen\ScreenRequest;
use App\Models\ScreeningResult;
use App\Models\TargetLookup;
use App\Services\ScreeningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ScreeningController extends BaseController
{
    public function __construct(protected ScreeningService $screeningService) {}

    // ──────────────────────────────────────────────────────────────────────────
    // GET /api/v1/screen/targets/{disease_name}
    // ──────────────────────────────────────────────────────────────────────────
    public function targets(string $disease_name): JsonResponse
    {
        $output = $this->screeningService->getTargets($disease_name);

        TargetLookup::create([
            'user_id' => Auth::id(),
            'input'   => ['disease_name' => $disease_name],
            'output'  => $output,
        ]);

        return response()->json($output);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // POST /api/v1/screen/screen
    // ──────────────────────────────────────────────────────────────────────────
    public function screen(ScreenRequest $request): JsonResponse
    {
        $input  = $request->validated();
        $output = $this->screeningService->screen($input);

        ScreeningResult::create([
            'user_id' => Auth::id(),
            'input'   => $input,
            'output'  => $output,
        ]);

        return response()->json($output);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // GET /api/v1/screen/history/targets
    // ──────────────────────────────────────────────────────────────────────────
    public function historyTargets(): JsonResponse
    {
        $results = TargetLookup::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get(['id', 'input', 'output', 'created_at']);

        return response()->json($results);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // GET /api/v1/screen/history/screening
    // ──────────────────────────────────────────────────────────────────────────
    public function historyScreening(): JsonResponse
    {
        $results = ScreeningResult::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get(['id', 'input', 'output', 'created_at']);

        return response()->json($results);
    }
}
