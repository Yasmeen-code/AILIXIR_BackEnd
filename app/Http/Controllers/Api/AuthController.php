<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\User\LoginRequest;
use App\Http\Requests\User\RegisterRequest;
use App\Http\Requests\User\LoginGoogleRequest;
use App\Http\Requests\User\CheckEmailRequest;
use Illuminate\Http\Request;
use App\Services\UserService;
use App\Services\OtpService;
use App\Models\User;

class AuthController extends BaseController
{

    protected $userService;
    protected $otpService;

    public function __construct(UserService $userService, OtpService $otpService)
    {
        $this->userService = $userService;
        $this->otpService = $otpService;
    }

    /** ---------------- Register (إرسال OTP للتحقق) ---------------- */

    public function register(RegisterRequest $request)
    {
        $user = $this->userService->registerUser($request->validated());

        // إرسال OTP للتحقق من البريد
        $this->otpService->sendOtp($user, 'email_verification');

        return $this->successResponse(
            'Registered successfully. Please check your email for OTP verification code.',
            ['email' => $user->email]
        );
    }

    /** ---------------- Login العادي (بدون OTP) ---------------- */

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

    /** ---------------- Forgot Password (إرسال OTP لإعادة التعيين) ---------------- */

    public function forgotPassword(CheckEmailRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        // إرسال OTP لإعادة تعيين كلمة المرور
        $this->otpService->sendOtp($user, 'password_reset');

        return $this->successResponse(
            'Password reset OTP has been sent to your email.',
            ['email' => $user->email]
        );
    }

    /** ---------------- Resend OTP (إعادة إرسال) ---------------- */

    public function resendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'type' => 'required|in:email_verification,password_reset'
        ]);

        $user = User::where('email', $request->email)->first();

        try {
            $this->otpService->resendOtp($user, $request->type);

            return $this->successResponse(
                'OTP has been resent to your email.',
                ['email' => $user->email]
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 429);
        }
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
