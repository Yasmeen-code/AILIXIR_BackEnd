<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\User\ResetPasswordRequest;
use App\Models\User;
use App\Http\Requests\User\CheckEmailRequest;
use App\Services\OtpService;
use App\Helpers\OtpHelper;

class PasswordResetController extends BaseController
{

    protected OtpService $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    /** ---------------- Password Reset ---------------- */

    public function sendForgotPasswordOtp(CheckEmailRequest $request)
    {
        $data = OtpHelper::sendOtpByType($request->validated()['email'], 'password_reset', $this->otpService);
        return $this->successResponse('OTP sent successfully', $data);
    }

    public function resendResetPasswordOtp(CheckEmailRequest $request)
    {
        $data = OtpHelper::sendOtpByType($request->validated()['email'], 'password_reset', $this->otpService);
        return $this->successResponse('OTP resent successfully', $data);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $validated = $request->validated();

        $user = User::where('email', $validated['email'])->firstOrFail();

        $this->otpService->resetPasswordWithOtp(
            $user,
            $validated['otp'],
            $validated['password']
        );

        return $this->successResponse('Password reset successfully');
    }
}
