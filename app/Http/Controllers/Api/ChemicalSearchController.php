<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChemicalSearchRequest;
use App\Models\ChemicalSearchJob;
use App\Services\ChemicalSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ChemicalSearchController extends BaseController
{
    public function __construct(
        private ChemicalSearchService $searchService
    ) {}

    /**
     * POST /api/chemical-search
     * Retrieval Only
     */
    public function store(ChemicalSearchRequest $request): JsonResponse
    {
        $user = Auth::user();

        $job = ChemicalSearchJob::create([
            'user_id' => $user->id,
            'query_smiles' => $request->validated('smiles'),
            'top_k' => $request->input('top_k', 5),
            'status' => 'pending',
        ]);

        dispatch(function () use ($job) {
            app(ChemicalSearchService::class)->search($job);
        })->onQueue('chemical-search');

        return response()->json([
            'success' => true,
            'job_id' => $job->id,
            'status' => 'pending',
            'check_url' => url("/api/chemical-search/{$job->id}/status"),
        ], 202);
    }

    /**
     * POST /api/chemical-search/full-rag
     * Full RAG (مع reason)
     */
    public function fullRag(ChemicalSearchRequest $request): JsonResponse
    {
        $user = Auth::user();

        $job = ChemicalSearchJob::create([
            'user_id' => $user->id,
            'query_smiles' => $request->validated('smiles'),
            'top_k' => $request->input('top_k', 5),
            'status' => 'pending',
        ]);

        dispatch(function () use ($job) {
            app(ChemicalSearchService::class)->fullRag($job);
        })->onQueue('chemical-search');

        return response()->json([
            'success' => true,
            'job_id' => $job->id,
            'status' => 'pending',
            'type' => 'full_rag',
            'check_url' => url("/api/chemical-search/{$job->id}/status"),
        ], 202);
    }

    /**
     * GET /api/chemical-search/{id}/status
     */
    public function status(int $id): JsonResponse
    {
        $job = ChemicalSearchJob::with('compounds')->find($id);

        if (!$job) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        if ($job->status === 'failed') {
            return response()->json([
                'success' => false,
                'job_id' => $job->id,
                'status' => 'failed',
                'error' => $job->error_message,
            ]);
        }

        if (in_array($job->status, ['pending', 'processing'])) {
            return response()->json([
                'success' => true,
                'job_id' => $job->id,
                'status' => $job->status,
            ]);
        }

        return response()->json([
            'success' => true,
            'job_id' => $job->id,
            'status' => 'completed',
            'query' => [
                'smiles' => $job->query_smiles,
                'top_k' => $job->top_k,
            ],
            'compounds' => $job->compounds->map(function ($compound) {
                return [
                    'rank' => $compound->rank,
                    'smiles' => $compound->smiles,
                    'name' => $compound->name,
                    'cid' => $compound->cid,
                    'similarity' => $compound->similarity,
                    'explanation' => $compound->explanation,
                    'image_url' => $compound->image_url,
                ];
            }),
            'metadata' => $job->metadata,
        ]);
    }
    /**
     * GET /api/chemical-search/{id}/images
     */
    public function images(int $id): JsonResponse
    {
        $job = ChemicalSearchJob::where('user_id', Auth::id())->find($id);

        if (!$job || $job->status !== 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Not found or not completed'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'job_id' => $job->id,
            'image_urls' => $job->image_urls,
            'total_images' => count($job->image_urls ?? []),
        ]);
    }
}
