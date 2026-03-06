<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\User\LoginRequest;
use App\Http\Requests\User\ProfileRequest;
use App\Http\Requests\User\RegisterRequest;
use Illuminate\Http\Request;
use App\Models\User;
use App\Services\UserService;
use App\Services\ProfileService;
use App\Traits\HandlesOtp;
use App\Http\Requests\User\EmailVerificationRequest;
use App\Http\Requests\User\ResetPasswordRequest;
use Illuminate\Support\Facades\Http;

class UserController extends BaseController
{
    use HandlesOtp;

    protected UserService $userService;
    protected ProfileService $profileService;

    public function __construct(UserService $userService, ProfileService $profileService)
    {
        $this->userService = $userService;
        $this->profileService = $profileService;
    }

    /** ---------------- Auth ----------------------- */

    public function register(RegisterRequest $request)
    {
        try {
            $user = $this->userService->registerUser($request->validated());
            return $this->successResponse(
                'Registered successfully. Check your email for OTP.',
                ['email' => $user->email]
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Registration failed: ' . $e->getMessage(), 500);
        }
    }

    public function login(LoginRequest $request)
    {
        $user = $this->userService->loginUser($request->validated());

        if (isset($user['error'])) {
            return $this->errorResponse($user['error'], $user['code'] ?? 400);
        }

        return $this->successResponse('Login successful', [
            'token' => $user['token'],
            'user' => $user['user']
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return $this->successResponse('Logged out successfully');
    }

    /** ---------------- Google OAuth ---------------- */

    public function getGoogleAuthUrl()
    {
        return response()->json([
            'auth_url' => $this->userService->getGoogleAuthUrl()
        ]);
    }

    // public function handleGoogleCallback(Request $request)
    // {
    //     try {

    //         $code = $request->query('code');

    //         $result = $this->userService->loginGoogleUser($code);

    //         if (isset($result['error'])) {
    //             return $this->errorResponse($result['error'], $result['code'] ?? 400);
    //         }

    //         return $this->successResponse('Login successful', [
    //             'token' => $result['token'],
    //             'user' => $result['user']
    //         ]);

    //         // return response()->view('google.callback', [
    //         //     'token' => $result['token'],
    //         //     'user' => $result['user']
    //         // ]);
    //     } catch (\Exception $e) {
    //         return $this->errorResponse('Google login failed: ' . $e->getMessage(), 500);
    //     }
    // }

    public function handleGoogleCallback(Request $request)
    {
        $accessToken = $request->access_token;

        $response = Http::get(
            'https://www.googleapis.com/oauth2/v3/userinfo',
            ['access_token' => $accessToken]
        );

        if (!$response->ok()) {
            return response()->json(['error' => 'Invalid token'], 401);
        }

        $googleUser = $response->json();

        $user = User::updateOrCreate(
            ['google_id' => $googleUser['sub']],
            [
                'name' => $googleUser['name'] ?? '',
                'email' => $googleUser['email'] ?? '',
                'avatar' => $googleUser['picture'] ?? null,
            ]
        );

        // create your app token (Sanctum / JWT)
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token
        ]);
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
        try {
            $result = $this->profileService->updateProfile($request->user(), $request->validated(), $request);
            return $this->successResponse('Profile updated successfully', $result);
        } catch (\Exception $e) {
            return $this->errorResponse('Error: ' . $e->getMessage(), 500);
        }
    }

    /** ---------------- OTP / Email Verification ---------------- */

    public function verifyEmail(EmailVerificationRequest $request)
    {
        $request = $request->validated();
        $user = User::where('email', $request['email'])->firstOrFail();

        $this->validateOtp($user, 'email_verification_otp', 'email_verification_otp_expires_at', $request['otp']);

        $user->update([
            'email_verified_at' => now(),
            'is_verified' => true,
            'email_verification_otp' => null,
            'email_verification_otp_expires_at' => null,
        ]);

        return $this->successResponse('Email verified successfully');
    }

    public function resendOtp(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);
        $user = User::where('email', $request->email)->firstOrFail();

        $email = $this->resendOtp($user, 'email_verification');

        return $this->successResponse('OTP resent successfully', ['email' => $email]);
    }

    public function resendResetPasswordOtp(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);
        $user = User::where('email', $request->email)->firstOrFail();

        $email = $this->resendOtp($user, 'password_reset');

        return $this->successResponse('OTP resent successfully', ['email' => $email]);
    }

    public function sendForgotPasswordOtp(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);
        $user = User::where('email', $request->email)->firstOrFail();

        $this->userService->sendOtp($user, 'password_reset');

        return $this->successResponse('OTP sent', ['email' => $user->email]);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $validated = $request->validated();

        $user = User::where('email', $validated['email'])->firstOrFail();

        $this->resetPasswordWithOtp($user, $validated['otp'], $validated['password']);

        return $this->successResponse('Password reset successfully');
    }
}
