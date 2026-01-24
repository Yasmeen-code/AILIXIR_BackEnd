<?php

namespace App\Http\Controllers;

use App\Models\Award;
use Illuminate\Http\Request;

class AwardController extends Controller
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

        return response()->json($awards);
    }

    public function show($id)
    {
        $award = Award::with(['scientists' => function ($query) {
            $query->select('scientists.*');
        }])
            ->find($id);

        if (!$award) {
            return response()->json(['message' => 'Award not found'], 404);
        }

        return response()->json([
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
            return response()->json(['message' => 'Award not found'], 404);
        }

        return response()->json([
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
