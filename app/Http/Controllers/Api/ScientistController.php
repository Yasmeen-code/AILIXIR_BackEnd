<?php

namespace App\Http\Controllers;

use App\Models\Scientist;

class ScientistController extends Controller
{
    public function index()
    {
        return Scientist::with(['awards' => function ($query) {
            $query->select('awards.id', 'awards.name', 'awards.images');
        }])
            ->select('id', 'name', 'images', 'bio', 'field')
            ->get()
            ->map(function ($scientist) {
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
    }

    public function show($id)
    {
        $scientist = Scientist::with(['awards' => function ($query) {
            $query->select('awards.*');
        }])
            ->findOrFail($id);

        return [
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
        ];
    }
    public function getAwardsByScientist($id)
    {
        $scientist = Scientist::with(['awards' => function ($query) {
            $query->select('awards.id', 'awards.name', 'awards.category', 'awards.images');
        }])
            ->find($id);

        if (!$scientist) {
            return response()->json(['message' => 'Scientist not found'], 404);
        }

        return response()->json([
            'scientist_id' => $scientist->id,
            'scientist_name' => $scientist->name,
            'awards' => $scientist->awards->map(function ($award) {
                return [
                    'id' => $award->id,
                    'name' => $award->name,
                    'category' => $award->category,
                    'image' => $award->images[0] ?? null,
                    'year_won' => $award->pivot->year_won,
                    'contribution' => $award->pivot->contribution,
                ];
            }),
        ]);
    }
}
