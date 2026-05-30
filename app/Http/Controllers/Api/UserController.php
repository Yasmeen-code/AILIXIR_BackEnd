<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\User\ProfileRequest;
use Illuminate\Http\Request;
use App\Services\ProfileService;

class UserController extends BaseController
{

    protected $profileService;

    public function __construct(ProfileService $profileService)
    {
        $this->profileService = $profileService;
    }

    /** ---------------- Profile ---------------- */

    public function profile(Request $request)
    {
        $user = $request->user()->load('researcher');

        return $this->successResponse('Profile retrieved', [
            'results' => [
                [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'researcher' => $user->researcher,
                    'photo' => $user->photo,
                ]
            ],
            'pagination' => [
                'currentPage' => 1,
                'totalPages' => 1,
                'totalResults' => 1,
                'perPage' => 1,
                'hasNextPage' => false,
                'hasPrevPage' => false
            ]
        ]);
    }

    public function updateProfile(ProfileRequest $request)
    {
        $result = $this->profileService->updateProfile(
            $request->user(),
            $request->validated(),
            $request
        );

        return $this->successResponse('Profile updated successfully', [
            'results' => [$result],
            'pagination' => [
                'currentPage' => 1,
                'totalPages' => 1,
                'totalResults' => 1,
                'perPage' => 1,
                'hasNextPage' => false,
                'hasPrevPage' => false
            ]
        ]);
    }
}
