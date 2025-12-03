<?php

namespace App\Http\Controllers;

use App\Models\Researcher;
use Illuminate\Http\Request;

class ResearcherController extends Controller
{
    public function updateProfile(Request $request)
    {
        $request->validate([
            'specialization' => 'nullable|string|max:255',
            'university' => 'nullable|string|max:255',
            'years_of_experience' => 'nullable|integer',
            'bio' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $user = $request->user();

        $researcher = Researcher::firstOrCreate(
            ['user_id' => $user->id]
        );

        if ($user->role !== 'researcher') {
            $user->role = 'researcher';
            $user->save();
        }

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('researchers', 'public');
            $researcher->photo = $path;
        }

        $researcher->specialization = $request->specialization;
        $researcher->university = $request->university;
        $researcher->years_of_experience = $request->years_of_experience;
        $researcher->bio = $request->bio;
        $researcher->save();

        return response()->json([
            'message' => 'Profile updated successfully',
            'researcher' => $researcher
        ]);
    }

    public function getFullProfile(Request $request)
    {
        $user = $request->user()->load('researcher');

        return response()->json([
            'user' => $user,
            'researcher' => $user->researcher
        ]);
    }
}
