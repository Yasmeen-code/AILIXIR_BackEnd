<?php

namespace App\Services;

use App\Jobs\SendOtpEmailJob;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class OtpService
{
    const OTP_EXPIRATION_MINUTES = 5;

    public function sendOtp(User $user, string $type): int
    {
        $otp = random_int(100000, 999999);
        $columns = $this->getOtpColumns($type);

        $user->{$columns['otp']} = $otp;
        $user->{$columns['expires']} = now()->addMinutes(self::OTP_EXPIRATION_MINUTES);
        $user->save();

        SendOtpEmailJob::dispatch($user, $otp, $type);

        Log::info("OTP for {$user->email} IS : {$otp} ");

        return $otp;
    }

    public function verifyOtp(User $user, string $type, string|int $otp): bool
    {
        $columns = $this->getOtpColumns($type);

        if ((int) $user->{$columns['otp']} !== (int) $otp) {
            throw new \Exception('Invalid OTP');
        }

        if ($user->{$columns['expires']} < now()) {
            throw new \Exception('OTP expired');
        }

        $user->{$columns['otp']} = null;
        $user->{$columns['expires']} = null;
        $user->save();

        return true;
    }

    private function getOtpColumns(string $type): array
    {
        return match ($type) {
            'email_verification' => [
                'otp' => 'email_verification_otp',
                'expires' => 'email_verification_otp_expires_at',
            ],
            'password_reset' => [
                'otp' => 'password_reset_otp',
                'expires' => 'password_reset_otp_expires_at',
            ],
            default => throw new InvalidArgumentException("Invalid OTP type: {$type}"),
        };
    }

    public function validateOtp(User $user, string $otpField, string $expiresField, string $inputOtp)
    {
        if ($inputOtp != $user->$otpField) {
            throw ValidationException::withMessages(['otp' => 'Invalid OTP']);
        }

        if (! $user->$expiresField || now()->gt($user->$expiresField)) {
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
            $secondsRemaining = round(now()->diffInSeconds($user->$otpExpiresField));
            throw ValidationException::withMessages(['otp' => "Please wait {$secondsRemaining} seconds before requesting a new OTP."]);
        }

        $this->sendOtp($user, $type);

        return $user->email;
    }

    public function resetPasswordWithOtp(User $user, string $inputOtp, string $newPassword)
    {
        $this->validateOtp($user, 'password_reset_otp', 'password_reset_otp_expires_at', $inputOtp);

        $user->update([
            'password' => Hash::make($newPassword),
            'password_reset_otp' => null,
            'password_reset_otp_expires_at' => null,
        ]);
    }
}
