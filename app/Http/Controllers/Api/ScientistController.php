<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Models\Scientist;
use Illuminate\Http\Request;

class ScientistController extends BaseController
{
    public function index(Request $request)
    {
        $perPage = min(100, max(1, (int) $request->get('per_page', 10)));

        $scientists = Scientist::with(['awards' => function ($query) {
            $query->select('awards.id', 'awards.name', 'awards.images');
        }])
            ->select('id', 'name', 'images', 'bio', 'field')
            ->paginate($perPage);

        $results = $scientists->getCollection()->map(function ($scientist) {
            return [
                'id' => $scientist->id,
                'name' => $scientist->name,
                'images' => $scientist->images,
                'field' => $scientist->field,
                'short_bio' => substr($scientist->bio, 0, 150) . '...',
                'awards_count' => $scientist->awards->count(),
                'awards' => $scientist->awards->map(function ($award) {
                    return [
                        'id' => $award->id,
                        'name' => $award->name,
                        'image' => $award->images[0] ?? null,
                        'year_won' => $award->pivot->year_won,
                    ];
                }),
            ];
        });

        return $this->successResponse('Scientists retrieved successfully', [
            'results' => $results,
            'pagination' => [
                'currentPage' => $scientists->currentPage(),
                'totalPages' => $scientists->lastPage(),
                'totalResults' => $scientists->total(),
                'perPage' => $scientists->perPage(),
                'hasNextPage' => $scientists->hasMorePages(),
                'hasPrevPage' => !$scientists->onFirstPage()
            ]
        ]);
    }

    public function show($id)
    {
        $scientist = Scientist::with(['awards' => function ($query) {
            $query->select('awards.*');
        }])
            ->find($id);

        if (!$scientist) {
            return $this->errorResponse('Scientist not found', 404);
        }

        return $this->successResponse('Scientist retrieved successfully', [
            'id' => $scientist->id,
            'name' => $scientist->name,
            'nationality' => $scientist->nationality,
            'birth_year' => $scientist->birth_year,
            'death_year' => $scientist->death_year,
            'field' => $scientist->field,
            'images' => $scientist->images,
            'bio' => $scientist->bio,
            'impact' => $scientist->impact,
            'awards' => $scientist->awards->map(function ($award) {
                return [
                    'id' => $award->id,
                    'name' => $award->name,
                    'category' => $award->category,
                    'images' => $award->images,
                    'year_won' => $award->pivot->year_won,
                    'contribution' => $award->pivot->contribution,
                ];
            }),
        ]);
    }

    public function getAwardsByScientist($id)
    {
        $scientist = Scientist::with(['awards' => function ($query) {
            $query->select('awards.id', 'awards.name', 'awards.category', 'awards.images');
        }])
            ->find($id);

        if (!$scientist) {
            return $this->errorResponse('Scientist not found', 404);
        }

        return $this->successResponse('Awards retrieved successfully', [
            'scientist_id' => $scientist->id,
            'scientist_name' => $scientist->name,
            'results' => $scientist->awards->map(function ($award) {
                return [
                    'id' => $award->id,
                    'name' => $award->name,
                    'category' => $award->category,
                    'image' => $award->images[0] ?? null,
                    'year_won' => $award->pivot->year_won,
                    'contribution' => $award->pivot->contribution,
                ];
            }),
            'pagination' => null
        ]);
    }
}
