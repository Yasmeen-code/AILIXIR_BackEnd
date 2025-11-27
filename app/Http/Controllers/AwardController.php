<?php

namespace App\Http\Controllers;

use App\Models\Award;
use Illuminate\Http\Request;

class AwardController extends Controller
{
    public function index()
    {
        $awards = Award::all()->map(function ($award) {
            return [
                'id' => $award->id,
                'name' => $award->name,
                'images' => $award->images,
                'short_description' => substr($award->description, 0, 100) . '...',
            ];
        });

        return response()->json($awards);
    }

    public function show($id)
    {
        $award = Award::find($id);

        if (!$award) {
            return response()->json(['message' => 'Award not found'], 404);
        }

        return response()->json([
            'id' => $award->id,
            'name' => $award->name,
            'category' => $award->category,
            'images' => $award->images,
            'description' => $award->description,
            'notable_winners' => $award->notable_winners,
            'country' => $award->country,
            'year_started' => $award->year_started,
            'website' => $award->website,
        ]);
    }
}
