<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Screen\ScreenRequest;
use App\Models\ScreeningResult;
use App\Models\TargetLookup;
use App\Jobs\RunTargetLookupJob;
use App\Jobs\RunScreeningJob;
use App\Services\ScreeningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScreeningController extends BaseController
{
    public function __construct(protected ScreeningService $screeningService) {}

    // ──────────────────────────────────────────────────────────────────────────
    // GET /api/v1/screen/targets/{disease_name}
    // ──────────────────────────────────────────────────────────────────────────
    public function targets(string $disease_name): JsonResponse
    {
        $lookup = TargetLookup::create([
            'user_id' => Auth::id(),
            'input'   => ['disease_name' => $disease_name],
            'status'  => 'pending',
        ]);

        RunTargetLookupJob::dispatch($lookup);

        return $this->successResponse('Target lookup queued successfully', [
            'job_id' => $lookup->id,
            'status' => $lookup->status,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // POST /api/v1/screen/screen
    // ──────────────────────────────────────────────────────────────────────────
    public function screen(ScreenRequest $request): JsonResponse
    {
        $input = $request->validated();

        $result = ScreeningResult::create([
            'user_id' => Auth::id(),
            'input'   => $input,
            'status'  => 'pending',
        ]);

        RunScreeningJob::dispatch($result);

        return $this->successResponse('Screening queued successfully', [
            'job_id' => $result->id,
            'status' => $result->status,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // GET /api/v1/screen/history/targets
    // ──────────────────────────────────────────────────────────────────────────
    public function historyTargets(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 15), 100);

        $paginator = TargetLookup::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['id', 'input', 'output', 'status', 'created_at']);

        return $this->successResponse('Target lookup history retrieved successfully', [
            'data'       => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
                'has_more'     => $paginator->hasMorePages(),
            ],
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // GET /api/v1/screen/history/screening
    // ──────────────────────────────────────────────────────────────────────────
    public function historyScreening(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 15), 100);

        $paginator = ScreeningResult::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['id', 'input', 'output', 'status', 'created_at']);

        return $this->successResponse('Screening history retrieved successfully', [
            'data'       => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
                'has_more'     => $paginator->hasMorePages(),
            ],
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // GET /api/v1/screen/targets/{id}
    // ──────────────────────────────────────────────────────────────────────────
    public function statusTargets(int $id): JsonResponse
    {
        $lookup = TargetLookup::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$lookup) {
            return $this->errorResponse('Target lookup not found or unauthorized', 404);
        }

        return $this->successResponse('Target lookup status retrieved successfully', [
            'job_id'     => $lookup->id,
            'status'     => $lookup->status,
            'input'      => $lookup->input,
            'output'     => $lookup->output,
            'created_at' => $lookup->created_at,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // GET /api/v1/screen/{id}
    // ──────────────────────────────────────────────────────────────────────────
    public function statusScreening(int $id): JsonResponse
    {
        $result = ScreeningResult::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$result) {
            return $this->errorResponse('Screening result not found or unauthorized', 404);
        }

        return $this->successResponse('Screening status retrieved successfully', [
            'job_id'     => $result->id,
            'status'     => $result->status,
            'input'      => $result->input,
            'output'     => $result->output,
            'created_at' => $result->created_at,
        ]);
    }
}
