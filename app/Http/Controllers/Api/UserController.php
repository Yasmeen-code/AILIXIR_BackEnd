<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use App\Models\Researcher;
use Illuminate\Validation\Rule;
use Cloudinary\Cloudinary;
use Illuminate\Support\Arr;

class UserController extends BaseController
{
    // ================== GENERATE & SEND OTP ==================
    private function generateAndSendOtp(User $user, string $type)
    {
        $otp = random_int(100000, 999999);
        $expiresAt = now()->addMinutes(15);

        if ($type === 'email_verification') {
            $user->email_verification_otp = $otp;
            $user->email_verification_otp_expires_at = $expiresAt;
            $subject = 'Email Verification OTP';
        } elseif ($type === 'password_reset') {
            $user->password_reset_otp = $otp;
            $user->password_reset_otp_expires_at = $expiresAt;
            $subject = 'Password Reset OTP';
        }

        $user->save();

        // إرسال البريد النصي (بدون View)
        Mail::raw("Your AILIXIR OTP is: $otp\n\nThis code expires in 15 minutes.\n\nIf you didn't request this, please ignore this email.", function ($mail) use ($user, $subject) {
            $mail->to($user->email)->subject($subject);
        });

        return $otp;
    }

    // ================== REGISTER ==================
    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:6|confirmed',
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'normal',
                'is_verified' => false,
            ]);

            $otp = $this->generateAndSendOtp($user, 'email_verification');

            // ⚠️ للتجربة في Postman: إرجاع الـ OTP
            // في Production: شيلي 'otp' من الـ Response
            return $this->successResponse(
                'Registered successfully. Please verify your email.',
                [
                    'email' => $user->email,
                    'otp' => $otp // ← شيلي هذا السطر في Production
                ]
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Registration failed: ' . $e->getMessage(), 500);
        }
    }

    // ================== VERIFY EMAIL ==================
    public function verifyEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|digits:6'
        ]);

        $user = User::where('email', $request->email)->first();

        // التحقق من صلاحية الـ OTP (بدون دوال الموديل)
        if (
            $user->email_verification_otp !== $request->otp ||
            !$user->email_verification_otp_expires_at ||
            $user->email_verification_otp_expires_at->isPast()
        ) {
            return $this->errorResponse('Invalid or expired OTP', 400);
        }

        // تحديث المستخدم
        $user->update([
            'email_verified_at' => now(),
            'is_verified' => true,
            'email_verification_otp' => null,
            'email_verification_otp_expires_at' => null,
        ]);

        return $this->successResponse('Email verified successfully');
    }

    // ================== LOGIN ==================
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user)
            return $this->errorResponse('Email not found', 404);

        if (!Hash::check($validated['password'], $user->password))
            return $this->errorResponse('Incorrect password', 401);

        if (!$user->is_verified)
            return $this->errorResponse('Please verify your email first', 403);

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->successResponse('Login successful', [
            'token' => $token,
            'user' => $user
        ]);
    }

    // ================== PROFILE ==================
    public function profile(Request $request)
    {
        $user = $request->user()->load('researcher');
        return $this->successResponse('Profile retrieved', [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'researcher' => $user->researcher,
            'photo' => $user->photo,
        ]);
    }

    // ================== LOGOUT ==================
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return $this->successResponse('Logged out successfully');
    }

    // ================== UPDATE PROFILE ==================
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => 'sometimes|string|min:6|confirmed',
            'specialization' => 'sometimes|string|max:255',
            'university' => 'sometimes|string|max:255',
            'years_of_experience' => 'sometimes|integer|min:0',
            'bio' => 'sometimes|string',
            'photo' => 'sometimes|file|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        try {
            $photoUrl = null;

            if ($request->hasFile('photo')) {
                if (!env('CLOUDINARY_API_SECRET')) {
                    return $this->errorResponse('Cloudinary API Secret missing in Railway', 500);
                }

                $cloudinary = new Cloudinary([
                    'cloud' => [
                        'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
                        'api_key'    => env('CLOUDINARY_API_KEY'),
                        'api_secret' => env('CLOUDINARY_API_SECRET'),
                    ],
                    'url' => ['secure' => true]
                ]);

                $file = $request->file('photo');
                $result = $cloudinary->uploadApi()->upload(
                    $file->getRealPath(),
                    [
                        'resource_type' => 'auto',
                        'public_id' => 'users/' . $user->id . '_' . time(),
                        'overwrite' => true,
                    ]
                );
                $photoUrl = $result['secure_url'];
            }

            $userData = Arr::except($validated, ['photo', 'password', 'specialization', 'university', 'years_of_experience', 'bio']);

            if (isset($validated['password'])) {
                $userData['password'] = Hash::make($validated['password']);
            }

            if ($photoUrl) {
                $userData['photo'] = $photoUrl;
            }

            $user->fill($userData);

            if ($user->role === 'normal' && ($request->filled('specialization') || $request->filled('university'))) {
                $user->role = 'researcher';
                $user->save();

                Researcher::create([
                    'user_id' => $user->id,
                    'specialization' => $request->specialization,
                    'university' => $request->university,
                    'years_of_experience' => $request->years_of_experience ?? 0,
                    'bio' => $request->bio,
                    'photo' => $photoUrl,
                ]);
            } else {
                $user->save();

                if ($user->researcher) {
                    $researcherData = [
                        'specialization' => $request->specialization ?? $user->researcher->specialization,
                        'university' => $request->university ?? $user->researcher->university,
                        'years_of_experience' => $request->years_of_experience ?? $user->researcher->years_of_experience,
                        'bio' => $request->bio ?? $user->researcher->bio,
                    ];

                    if ($photoUrl) {
                        $researcherData['photo'] = $photoUrl;
                    }

                    $user->researcher->update($researcherData);
                }
            }

            return $this->successResponse('Profile updated', $user->fresh()->load('researcher'));
        } catch (\Exception $e) {
            return $this->errorResponse('Error: ' . $e->getMessage(), 500);
        }
    }

    // ================== FORGOT PASSWORD - SEND OTP ==================
    public function sendForgotPasswordOtp(Request $request)
    {
        $validated = $request->validate(['email' => 'required|email']);

        $user = User::where('email', $validated['email'])->first();
        if (!$user) return $this->errorResponse('User not found', 404);

        $otp = $this->generateAndSendOtp($user, 'password_reset');

        // ⚠️ للتجربة في Postman: إرجاع الـ OTP
        // في Production: شيلي 'otp' من الـ Response
        return $this->successResponse('OTP sent', [
            'email' => $user->email,
            'otp' => $otp // ← شيلي هذا السطر في Production
        ]);
    }

    // ================== RESET PASSWORD ==================
    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'otp' => 'required|digits:6',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::where('email', $validated['email'])->first();
        if (!$user) return $this->errorResponse('User not found', 404);

        // التحقق من صلاحية الـ OTP
        if (
            $user->password_reset_otp !== $validated['otp'] ||
            !$user->password_reset_otp_expires_at ||
            $user->password_reset_otp_expires_at->isPast()
        ) {
            return $this->errorResponse('Invalid or expired OTP', 400);
        }

        $user->password = Hash::make($validated['password']);
        $user->password_reset_otp = null;
        $user->password_reset_otp_expires_at = null;
        $user->save();

        return $this->successResponse('Password reset successfully');
    }
}
