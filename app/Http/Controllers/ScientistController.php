<?php

namespace App\Http\Controllers;

use App\Models\Scientist;

class ScientistController extends Controller
{
    public function index()
    {
        return Scientist::select('id', 'name', 'image_url', 'bio')
            ->get()
            ->map(function ($s) {
                $s->short_bio = substr($s->bio, 0, 150) . '...';
                return $s;
            });
    }

    public function show($id)
    {
        return Scientist::findOrFail($id);
    }
}
