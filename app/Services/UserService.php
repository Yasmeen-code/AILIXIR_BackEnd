<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserService
{
    protected $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    public function registerUser(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'normal',
            'is_verified' => false,
        ]);

        $this->otpService->resendOtp($user, 'email_verification');

        return $user;
    }

    public function loginUser(array $credentials): array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return ['error' => 'Invalid email or password', 'code' => 401];
        }

        if (!$user->is_verified) {
            $this->otpService->resendOtp($user, 'email_verification');
            return ['error' => 'Email not verified. OTP sent again.', 'code' => 403];
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }

    public function loginGoogleUser(string $accessToken): array
    {
        $response = \Illuminate\Support\Facades\Http::withToken($accessToken)
            ->get('https://www.googleapis.com/oauth2/v3/userinfo');

        if (!$response->ok()) {
            return ['error' => 'Invalid Google token', 'code' => 401];
        }

        $googleUser = $response->json();

        $user = User::updateOrCreate(
            ['email' => $googleUser['email']],
            [
                'name' => $googleUser['name'] ?? '',
                'password' => Hash::make(Str::random(32)),
                'role' => 'normal',
                'is_verified' => true,
                'photo' => $googleUser['picture'] ?? null,
            ]
        );

        $token = $user->createToken('auth_token')->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }

    public function resetPassword(User $user, string $otp, string $newPassword): bool
    {
        return $this->otpService->verifyOtp($user, 'password_reset', $otp) &&
            tap($user, fn($u) => $u->update(['password' => Hash::make($newPassword)]));
    }
}
