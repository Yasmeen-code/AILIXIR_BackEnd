<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Http\Resources\ScientistResource;
use App\Http\Resources\AwardResource;
use App\Services\AwardService;
use Illuminate\Http\Request;

class AwardController extends BaseController
{
    protected $service;

    public function __construct(AwardService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $perPage = min(100, max(1, (int)$request->get('per_page', 10)));

        $awards = $this->service->getAwards($perPage);

        return $this->paginatedResponse(
            'Awards retrieved successfully',
            $awards->through(fn($award) => new AwardResource($award)),
            $awards
        );
    }

    public function show($id)
    {
        $award = $this->service->getAward($id);

        return $this->successResponse(
            'Award retrieved successfully',
            new AwardResource($award)
        );
    }

    public function getScientistsByAward($id)
    {
        $award = $this->service->getAward($id);

        return $this->successResponse(
            'Scientists retrieved successfully',
            ScientistResource::collection($award->scientists)
        );
    }
}
