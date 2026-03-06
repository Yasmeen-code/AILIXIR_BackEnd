<?php

namespace App\Helpers;

use App\Models\User;
use App\Services\OtpService;

class OtpHelper
{
    /**
     * Send OTP by type for a user email
     *
     * @param string $email
     * @param string $type ('email_verification' or 'password_reset')
     * @param OtpService $otpService
     * @return array
     */
    public static function sendOtpByType(string $email, string $type, OtpService $otpService): array
    {
        $user = User::where('email', $email)->firstOrFail();

        $otpService->resendOtp($user, $type);

        return [
            'email' => $user->email
        ];
    }
}
