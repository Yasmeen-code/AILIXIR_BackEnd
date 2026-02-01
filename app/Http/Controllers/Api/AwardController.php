<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Models\Award;

class AwardController extends BaseController
{
    public function index()
    {
        $awards = Award::with(['scientists' => function ($query) {
            $query->select('scientists.id', 'scientists.name', 'scientists.images');
        }])
            ->get()
            ->map(function ($award) {
                return [
                    'id' => $award->id,
                    'name' => $award->name,
                    'images' => $award->images,
                    'category' => $award->category,
                    'short_description' => substr($award->description, 0, 100) . '...',
                    'scientists_count' => $award->scientists->count(),
                    'scientists' => $award->scientists->map(function ($scientist) {
                        return [
                            'id' => $scientist->id,
                            'name' => $scientist->name,
                            'image' => $scientist->images[0] ?? null,
                            'year_won' => $scientist->pivot->year_won,
                        ];
                    }),
                ];
            });

        return $this->listResponse('Awards retrieved successfully', $awards);
    }

    public function show($id)
    {
        $award = Award::with(['scientists' => function ($query) {
            $query->select('scientists.*');
        }])
            ->find($id);

        if (!$award) {
            return $this->errorResponse('Award not found', 404);
        }

        return $this->successResponse('Award retrieved successfully', [
            'id' => $award->id,
            'name' => $award->name,
            'category' => $award->category,
            'images' => $award->images,
            'description' => $award->description,
            'country' => $award->country,
            'year_started' => $award->year_started,
            'website' => $award->website,
            'scientists' => $award->scientists->map(function ($scientist) {
                return [
                    'id' => $scientist->id,
                    'name' => $scientist->name,
                    'nationality' => $scientist->nationality,
                    'images' => $scientist->images,
                    'field' => $scientist->field,
                    'year_won' => $scientist->pivot->year_won,
                    'contribution' => $scientist->pivot->contribution,
                ];
            }),
        ]);
    }

    public function getScientistsByAward($id)
    {
        $award = Award::with(['scientists' => function ($query) {
            $query->select('scientists.id', 'scientists.name', 'scientists.images', 'scientists.field');
        }])
            ->find($id);

        if (!$award) {
            return $this->errorResponse('Award not found', 404);
        }

        return $this->successResponse('Scientists retrieved successfully', [
            'award_id' => $award->id,
            'award_name' => $award->name,
            'scientists' => $award->scientists->map(function ($scientist) {
                return [
                    'id' => $scientist->id,
                    'name' => $scientist->name,
                    'field' => $scientist->field,
                    'image' => $scientist->images[0] ?? null,
                    'year_won' => $scientist->pivot->year_won,
                    'contribution' => $scientist->pivot->contribution,
                ];
            }),
        ]);
    }
}
