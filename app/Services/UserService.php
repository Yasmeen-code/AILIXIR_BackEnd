<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class UserService
{
    protected UserRepository $userRepo;
    const OTP_EXPIRATION_MINUTES = 15;

    public function __construct(UserRepository $userRepo)
    {
        $this->userRepo = $userRepo;
    }

    public function registerUser(array $data): User
    {
        $user = $this->userRepo->create([
            'name'        => $data['name'],
            'email'       => $data['email'],
            'password'    => Hash::make($data['password']),
            'role'        => 'normal',
            'is_verified' => false,
        ]);

        $this->sendOtp($user, 'email_verification');

        return $user;
    }

    public function sendOtp(User $user, string $type): int
    {
        $otp = random_int(100000, 999999);
        $expiresAt = now()->addMinutes(self::OTP_EXPIRATION_MINUTES);

        $otpColumns = match ($type) {
            'email_verification' => ['otp' => 'email_verification_otp', 'expires' => 'email_verification_otp_expires_at'],
            'password_reset'     => ['otp' => 'password_reset_otp', 'expires' => 'password_reset_otp_expires_at'],
            default => throw new \InvalidArgumentException("Invalid OTP type: $type"),
        };

        $user->{$otpColumns['otp']} = $otp;
        $user->{$otpColumns['expires']} = $expiresAt;
        $this->userRepo->save($user);

        $subject = $type === 'email_verification' ? 'Email Verification OTP' : 'Password Reset OTP';

        Mail::raw(
            "Your AILIXIR OTP is: $otp\nExpires in " . self::OTP_EXPIRATION_MINUTES . " minutes.",
            fn($message) => $message->to($user->email)->subject($subject)
        );

        return $otp;
    }

    public function loginUser(array $credentials): array
    {
        $user = $this->userRepo->findByEmail($credentials['email']);

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return $this->errorResponse('Invalid email or password.', 401);
        }

        if (!$user->is_verified) {
            return $this->errorResponse('Please verify your email first.', 403);
        }

        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user'  => $user,
            'token' => $token,
        ];
    }

    public function getGoogleAuthUrl(): string
    {
        return Socialite::driver('google')->stateless()->redirect()->getTargetUrl();
    }

    public function loginGoogleUser(string $code): array
    {
        $accessToken = $this->getGoogleAccessToken($code);

        if (!$accessToken) {
            return $this->errorResponse('Failed to retrieve access token from Google.', 500);
        }

        $googleUser = Socialite::driver('google')->stateless()->userFromToken($accessToken);

        $user = $this->userRepo->firstOrCreate(
            ['email' => $googleUser->getEmail()],
            [
                'name'        => $googleUser->getName(),
                'password'    => Hash::make(Str::random(32)),
                'role'        => 'normal',
                'is_verified' => true,
                'photo'       => $googleUser->getAvatar(),
            ]
        );

        $token = $user->createToken('desktop_token')->plainTextToken;

        return [
            'user'  => $user,
            'token' => $token,
        ];
    }

    private function getGoogleAccessToken(string $code): ?string
    {
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id'     => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'redirect_uri'  => config('services.google.redirect'),
            'grant_type'    => 'authorization_code',
            'code'          => $code,
        ]);

        return $response['access_token'] ?? null;
    }

    private function errorResponse(string $message, int $code): array
    {
        return [
            'error' => $message,
            'code'  => $code,
        ];
    }
}
