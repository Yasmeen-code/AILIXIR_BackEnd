<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use App\Models\Researcher;
use Illuminate\Validation\Rule;
use Cloudinary\Cloudinary;

class UserController extends Controller
{
    // ========== Helper Response Functions ==========
    private function success($message, $data = [], $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $code);
    }

    private function error($message, $code = 400)
    {
        return response()->json([
            'success' => false,
            'message' => $message
        ], $code);
    }

    // ========== Helper Pagination Response ==========
    private function paginationResponse($message, $results)
    {
        return $this->success($message, [
            'results' => $results->items(),
            'pagination' => [
                'currentPage'   => $results->currentPage(),
                'totalPages'    => $results->lastPage(),
                'totalResults'  => $results->total(),
                'hasNextPage'   => $results->hasMorePages(),
                'hasPrevPage'   => $results->currentPage() > 1,
            ]
        ]);
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

            // OTP
            $otp = random_int(100000, 999999);
            $user->email_verification_otp = $otp;
            $user->save();

            Mail::raw("Your verification OTP is: $otp", function ($message) use ($user) {
                $message->to($user->email)->subject('Email Verification OTP');
            });

            return $this->success(
                'Registered successfully. Please verify your email.',
                ['email' => $user->email]
            );
        } catch (\Exception $e) {
            return $this->error('Registration failed: ' . $e->getMessage(), 500);
        }
    }

    // ================== VERIFY EMAIL ==================
    public function verifyEmail(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'otp' => 'required|digits:6',
        ]);

        $user = User::where('email', $validated['email'])->first();
        if (!$user) return $this->error('User not found', 404);

        if ($user->is_verified)
            return $this->error('Email already verified');

        if ($user->email_verification_otp != $validated['otp'])
            return $this->error('Invalid OTP');

        $user->is_verified = true;
        $user->email_verification_otp = null;
        $user->email_verified_at = now(); // تحديث العمود بالوقت الحالي
        $user->save();

        return $this->success('Email verified successfully');
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
            return $this->error('Email not found', 404);

        if (!Hash::check($validated['password'], $user->password))
            return $this->error('Incorrect password', 401);

        if (!$user->is_verified)
            return $this->error('Please verify your email first', 403);

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->success('Login successful', [
            'token' => $token,
            'user' => $user
        ]);
    }

    // ================== PROFILE ==================
    public function profile(Request $request)
    {
        return $this->success('Profile retrieved', [
            'user' => $request->user()->load('researcher')
        ]);
    }

    // ================== LOGOUT ==================
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return $this->success('Logged out successfully');
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
            $user->fill($validated);

            if (isset($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }

            if ($request->hasFile('photo')) {
                try {
                    $cloudinary = new Cloudinary([
                        'cloud' => [
                            'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
                            'api_key'    => env('CLOUDINARY_API_KEY'),
                            'api_secret' => env('CLOUDINARY_API_SECRET'),
                        ],
                        'url' => ['secure' => true]
                    ]);

                    $result = $cloudinary->uploadApi()->upload(
                        $request->file('photo')->getRealPath(),
                        ['resource_type' => 'auto']
                    );

                    $validated['photo'] = $result['secure_url'];
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Error uploading photo: ' . $e->getMessage()
                    ], 500);
                }
            }

            if ($user->role === 'normal' && ($request->specialization || $request->university)) {
                $user->role = 'researcher';
                $user->save();

                Researcher::create([
                    'user_id' => $user->id,
                    'specialization' => $request->specialization,
                    'university' => $request->university,
                    'years_of_experience' => $request->years_of_experience,
                    'bio' => $request->bio,
                    'photo' => $validated['photo'] ?? null,
                ]);
            } else {
                $user->save();

                if ($user->researcher) {
                    $user->researcher->update($validated);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Profile updated',
                'data' => $user->load('researcher'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating profile: ' . $e->getMessage()
            ], 500);
        }
    }
    // ================== FORGOT PASSWORD - SEND OTP ==================
    public function sendForgotPasswordOtp(Request $request)
    {
        $validated = $request->validate(['email' => 'required|email']);

        $user = User::where('email', $validated['email'])->first();
        if (!$user) return $this->error('User not found', 404);

        $otp = random_int(100000, 999999);
        $user->password_reset_otp = $otp;
        $user->save();

        Mail::raw("Your password reset OTP is: $otp", function ($message) use ($user) {
            $message->to($user->email)->subject('Password Reset OTP');
        });

        return $this->success('OTP sent', ['email' => $user->email]);
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
        if (!$user) return $this->error('User not found', 404);

        if ($user->password_reset_otp != $validated['otp'])
            return $this->error('Invalid OTP');

        $user->password = Hash::make($validated['password']);
        $user->password_reset_otp = null;
        $user->save();

        return $this->success('Password reset successfully');
    }
}
