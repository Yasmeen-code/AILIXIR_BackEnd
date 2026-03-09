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
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'researcher' => $user->researcher,
            'photo' => $user->photo,
        ]);
    }

    public function updateProfile(ProfileRequest $request)
    {
        $result = $this->profileService->updateProfile(
            $request->user(),
            $request->validated(),
            $request
        );

        return $this->successResponse('Profile updated successfully', $result);
    }
}
