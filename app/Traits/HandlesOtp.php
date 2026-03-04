<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Validation\ValidationException;
use App\Services\UserService;

trait HandlesOtp
{

    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function validateOtp(User $user, string $otpField, string $expiresField, string $inputOtp)
    {
        if ($user->$otpField != $inputOtp) {
            throw ValidationException::withMessages(['otp' => 'Invalid OTP']);
        }

        if (!$user->$expiresField || now()->gt($user->$expiresField)) {
            throw ValidationException::withMessages(['otp' => 'Expired OTP']);
        }
    }


    public function resendOtp(User $user, string $type = 'email_verification')
    {
        $otpExpiresField = $type === 'password_reset' ? 'password_reset_otp_expires_at' : 'email_verification_otp_expires_at';

        if ($type === 'email_verification' && $user->is_verified) {
            throw ValidationException::withMessages(['email' => 'Email already verified.']);
        }

        if ($user->$otpExpiresField && now()->lt($user->$otpExpiresField)) {
            throw ValidationException::withMessages(['otp' => 'Please wait before requesting another OTP.']);
        }

        $this->userService->sendOtp($user, $type);

        return $user->email;
    }


    public function resetPasswordWithOtp(User $user, string $inputOtp, string $newPassword)
    {
        $this->validateOtp($user, 'password_reset_otp', 'password_reset_otp_expires_at', $inputOtp);

        $user->update([
            'password' => bcrypt($newPassword),
            'password_reset_otp' => null,
            'password_reset_otp_expires_at' => null,
        ]);
    }
}
