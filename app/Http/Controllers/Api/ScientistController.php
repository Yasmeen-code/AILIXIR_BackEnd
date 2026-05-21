<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Http\Resources\ScientistResource;
use App\Services\ScientistService;
use Illuminate\Http\Request;

class ScientistController extends BaseController
{
    protected $service;

    public function __construct(ScientistService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $perPage = min(100, max(1, (int)$request->get('per_page', 10)));

        $scientists = $this->service->getScientists($perPage);

        return $this->successResponse(
            'Scientists retrieved successfully',
            [
                'results' => ScientistResource::collection($scientists),
                'pagination' => [
                    'currentPage' => $scientists->currentPage(),
                    'totalPages' => $scientists->lastPage(),
                    'totalResults' => $scientists->total(),
                    'perPage' => $scientists->perPage(),
                    'hasNextPage' => $scientists->hasMorePages(),
                    'hasPrevPage' => !$scientists->onFirstPage()
                ]
            ]
        );
    }

    public function show($id)
    {
        $scientist = $this->service->getScientist($id);

        return $this->successResponse(
            'Scientist retrieved successfully',
            [
                'results' => [new ScientistResource($scientist)],
                'pagination' => [
                    'currentPage' => 1,
                    'totalPages' => 1,
                    'totalResults' => 1,
                    'perPage' => 1,
                    'hasNextPage' => false,
                    'hasPrevPage' => false
                ]
            ]
        );
    }

    public function getAwardsByScientist($id)
    {
        $scientist = $this->service->getScientist($id);

        $awards = $scientist->awards;

        return $this->successResponse(
            'Awards retrieved successfully',
            [
                'results' => $awards,
                'pagination' => [
                    'currentPage' => 1,
                    'totalPages' => 1,
                    'totalResults' => $awards->count(),
                    'perPage' => $awards->count(),
                    'hasNextPage' => false,
                    'hasPrevPage' => false
                ]
            ]
        );
    }
}
