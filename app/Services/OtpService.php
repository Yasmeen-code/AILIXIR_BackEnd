<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\SendOtpNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class OtpService
{
    const OTP_EXPIRATION_MINUTES = 5;

    /**
     * Send OTP to user via email
     */
    public function sendOtp(User $user, string $type): int
    {
        $otp = random_int(100000, 999999);
        $columns = $this->getOtpColumns($type);

        $user->{$columns['otp']} = $otp;
        $user->{$columns['expires']} = now()->addMinutes(self::OTP_EXPIRATION_MINUTES);
        $user->save();

        try {
            Log::info("Sending OTP to: {$user->email}, Type: {$type}, OTP: {$otp}");
            $user->notify(new SendOtpNotification($otp, $type));
            Log::info("OTP email sent successfully to: {$user->email}");
        } catch (\Exception $e) {
            Log::error("Failed to send OTP email: " . $e->getMessage());
            throw $e;
        }

        return $otp;
    }

    /**
     * Verify OTP and clear it from database
     * 
     * @throws \Exception
     */
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

    /**
     * Resend OTP with cooldown check
     * 
     * @throws ValidationException|InvalidArgumentException
     */
    public function resendOtp(User $user, string $type = 'email_verification'): string
    {
        if (!in_array($type, ['email_verification', 'password_reset'])) {
            throw new InvalidArgumentException("Invalid OTP type: {$type}");
        }

        $otpExpiresField = $type === 'password_reset'
            ? 'password_reset_otp_expires_at'
            : 'email_verification_otp_expires_at';

        if ($type === 'email_verification' && $user->is_verified) {
            throw ValidationException::withMessages([
                'email' => 'Email already verified.'
            ]);
        }

        // ✅ رجعنا الـ throw هنا - ده للـ resend endpoint العادي
        if ($user->$otpExpiresField && now()->lt($user->$otpExpiresField)) {
            $secondsRemaining = now()->diffInSeconds($user->$otpExpiresField);
            $timeText = $this->formatRemainingTime((int) $secondsRemaining);

            throw ValidationException::withMessages([
                'otp' => "Please wait {$timeText} before requesting a new OTP."
            ]);
        }

        $this->sendOtp($user, $type);

        return $user->email;
    }

    /**
     * Reset password using OTP
     * 
     * @throws \Exception|ValidationException
     */
    public function resetPasswordWithOtp(User $user, string $inputOtp, string $newPassword): void
    {
        $this->verifyOtp($user, 'password_reset', $inputOtp);

        $user->update([
            'password' => Hash::make($newPassword),
        ]);
    }

    /**
     * Get database columns for OTP type
     * 
     * @throws InvalidArgumentException
     */
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

    /**
     * Check if user can resend OTP or still has active one
     * 
     * @return array ['can_resend' => bool, 'remaining_seconds' => int|null]
     */
    public function canResendOtp(User $user, string $type): array
    {
        $columns = $this->getOtpColumns($type);
        $expiresField = $columns['expires'];

        // لو مفيش OTP expires field أو خلص
        if (!$user->$expiresField || now()->gte($user->$expiresField)) {
            return ['can_resend' => true, 'remaining_seconds' => null];
        }

        // لسه الـ OTP صالح
        $remainingSeconds = now()->diffInSeconds($user->$expiresField);

        return ['can_resend' => false, 'remaining_seconds' => $remainingSeconds];
    }

    /**
     * Format remaining time to human readable string
     */
    public function formatRemainingTime(int $seconds): string
    {
        if ($seconds >= 3600) {
            $hours = ceil($seconds / 3600);
            return "{$hours} " . ($hours === 1 ? 'hour' : 'hours');
        }

        if ($seconds >= 60) {
            $minutes = ceil($seconds / 60);
            return "{$minutes} " . ($minutes === 1 ? 'minute' : 'minutes');
        }

        $seconds = (int) $seconds;
        return "{$seconds} " . ($seconds === 1 ? 'second' : 'seconds');
    }
}
