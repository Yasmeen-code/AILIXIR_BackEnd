<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\ChemicalSearchRequest;
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
     * Retrieval Only - Synchronous
     */
    public function store(ChemicalSearchRequest $request): JsonResponse
    {
        $user = Auth::user();

        $result = $this->searchService->search(
            smiles: $request->validated('smiles'),
            topK: $request->input('top_k', 5),
            userId: $user->id
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'] ?? 'Search failed',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'query' => [
                'smiles' => $request->validated('smiles'),
                'top_k' => $request->input('top_k', 5),
            ],
            'compounds' => $result['compounds'],
            'metadata' => $result['metadata'],
        ]);
    }
    public function fullRag(ChemicalSearchRequest $request): JsonResponse
    {
        $user = Auth::user();

        $result = $this->searchService->fullRag(
            smiles: $request->validated('smiles'),
            topK: $request->input('top_k', 5),
            userId: $user->id
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'] ?? 'Search failed',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'query' => [
                'smiles' => $request->validated('smiles'),
                'top_k' => $request->input('top_k', 5),
            ],
            'compounds' => $result['compounds'],
            'metadata' => $result['metadata'],
        ]);
    }
}
