<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\User\EmailVerificationRequest;
use App\Http\Requests\User\ResetPasswordRequest;
use App\Models\User;
use App\Services\OtpService;

class OtpController extends BaseController
{
    protected $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * Verify Email OTP
     */
    public function verifyEmail(EmailVerificationRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return $this->errorResponse('User not found', 404);
        }

        try {
            // التحقق من OTP
            $this->otpService->verifyOtp($user, 'email_verification', $request->otp);

            // تحديث حالة التحقق
            $user->update(['is_verified' => true]);

            // إنشاء token للمستخدم
            $token = $user->createToken('auth_token')->plainTextToken;

            return $this->successResponse('Email verified successfully', [
                'token' => $token,
                'user' => $user
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Verify Password Reset OTP and reset password
     */
    public function verifyResetPassword(ResetPasswordRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return $this->errorResponse('User not found', 404);
        }

        try {
            $this->otpService->resetPasswordWithOtp($user, $request->otp, $request->password);

            return $this->successResponse('Password reset successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}
