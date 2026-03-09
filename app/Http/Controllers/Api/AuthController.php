<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\User\LoginRequest;
use App\Http\Requests\User\RegisterRequest;
use App\Http\Requests\User\LoginGoogleRequest;
use Illuminate\Http\Request;
use App\Services\UserService;

class AuthController extends BaseController
{

    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /** ---------------- Auth ---------------- */

    public function register(RegisterRequest $request)
    {
        $user = $this->userService->registerUser($request->validated());

        return $this->successResponse(
            'Registered successfully. Check your email for OTP.',
            ['email' => $user->email]
        );
    }

    public function login(LoginRequest $request)
    {
        $result = $this->userService->loginUser($request->validated());

        if (isset($result['error'])) {
            return $this->errorResponse($result['error'], $result['code'] ?? 400);
        }

        return $this->successResponse('Login successful', [
            'token' => $result['token'],
            'user'  => $result['user']
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse('Logged out successfully');
    }

    /** ---------------- Google OAuth ---------------- */

    public function handleGoogleCallback(LoginGoogleRequest $request)
    {
        $result = $this->userService->loginGoogleUser(
            $request->validated()['access_token']
        );

        if (isset($result['error'])) {
            return $this->errorResponse($result['error'], $result['code'] ?? 400);
        }

        return $this->successResponse('Google login successful', [
            'token' => $result['token'],
            'user'  => $result['user']
        ]);
    }
}
