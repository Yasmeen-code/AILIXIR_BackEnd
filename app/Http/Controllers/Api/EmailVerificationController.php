<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\User\EmailVerificationRequest;
use App\Models\User;
use App\Http\Requests\User\CheckEmailRequest;
use App\Services\OtpService;
use App\Helpers\OtpHelper;

class EmailVerificationController extends BaseController
{

    protected OtpService $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    /** ---------------- Email Verification ---------------- */

    public function verifyEmail(EmailVerificationRequest $request)
    {
        $data = $request->validated();

        $user = User::where('email', $data['email'])->firstOrFail();

        $this->otpService->validateOtp(
            $user,
            'email_verification_otp',
            'email_verification_otp_expires_at',
            $data['otp']
        );

        $user->update([
            'email_verified_at' => now(),
            'is_verified' => true,
            'email_verification_otp' => null,
            'email_verification_otp_expires_at' => null,
        ]);

        return $this->successResponse('Email verified successfully');
    }

    public function resendOtp(CheckEmailRequest $request)
    {
        $data = OtpHelper::sendOtpByType($request->validated()['email'], 'email_verification', $this->otpService);
        return $this->successResponse('OTP resent successfully', $data);
    }
}
