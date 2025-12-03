<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use App\Models\Researcher;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    // ================== REGISTER ==================
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'normal',
            'is_verified' => false, // new
        ]);

        // generate OTP
        $otp = rand(100000, 999999);
        $user->email_verification_otp = $otp;
        $user->save();

        // send OTP email
        Mail::raw("Your verification OTP is: $otp", function ($message) use ($user) {
            $message->to($user->email)->subject('Email Verification OTP');
        });

        return response()->json([
            'message' => 'Registered successfully. Please verify your email with the OTP sent.',
        ]);
    }

    // ================== VERIFY EMAIL ==================
    public function verifyEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|digits:6',
        ]);

        $user = User::where('email', $request->email)->first();
        if (!$user) return response()->json(['message' => 'User not found'], 404);

        if ($user->email_verification_otp == $request->otp) {
            $user->is_verified = true;
            $user->email_verification_otp = null;
            $user->save();
            return response()->json(['message' => 'Email verified successfully']);
        }

        return response()->json(['message' => 'Invalid OTP'], 400);
    }

    // ================== LOGIN ==================
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        if (!$user->is_verified) {
            return response()->json(['message' => 'Please verify your email first'], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => $user
        ]);
    }

    // ================== PROFILE ==================
    public function profile(Request $request)
    {
        $user = $request->user()->load('researcher');
        return response()->json($user);
    }

    // ================== LOGOUT ==================
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }

    // ================== UPDATE PROFILE ==================
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => 'sometimes|string|min:6|confirmed',
            'specialization' => 'sometimes|string|max:255',
            'university' => 'sometimes|string|max:255',
            'years_of_experience' => 'sometimes|integer|min:0',
            'bio' => 'sometimes|string',
            'photo' => 'sometimes|string|max:255',
        ]);

        if ($request->has('name')) $user->name = $request->name;
        if ($request->has('email')) $user->email = $request->email;
        if ($request->has('password')) $user->password = Hash::make($request->password);

        if ($user->role === 'normal') {
            $user->role = 'researcher';
            $user->save();

            $researcher = Researcher::create([
                'user_id' => $user->id,
                'specialization' => $request->specialization ?? null,
                'university' => $request->university ?? null,
                'years_of_experience' => $request->years_of_experience ?? null,
                'bio' => $request->bio ?? null,
                'photo' => $request->photo ?? null,
            ]);
        } else {
            $user->save();
            if ($user->researcher) {
                $user->researcher->update([
                    'specialization' => $request->specialization ?? $user->researcher->specialization,
                    'university' => $request->university ?? $user->researcher->university,
                    'years_of_experience' => $request->years_of_experience ?? $user->researcher->years_of_experience,
                    'bio' => $request->bio ?? $user->researcher->bio,
                    'photo' => $request->photo ?? $user->researcher->photo,
                ]);
            }
        }

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user->load('researcher')
        ]);
    }

    // ================== FORGOT PASSWORD - SEND OTP ==================
    public function sendForgotPasswordOtp(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $user = User::where('email', $request->email)->first();
        if (!$user) return response()->json(['message' => 'User not found'], 404);

        $otp = rand(100000, 999999);
        $user->password_reset_otp = $otp;
        $user->save();

        Mail::raw("Your password reset OTP is: $otp", function ($message) use ($user) {
            $message->to($user->email)->subject('Password Reset OTP');
        });

        return response()->json(['message' => 'OTP sent to your email']);
    }

    // ================== RESET PASSWORD ==================
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|digits:6',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::where('email', $request->email)->first();
        if (!$user) return response()->json(['message' => 'User not found'], 404);

        if ($user->password_reset_otp != $request->otp)
            return response()->json(['message' => 'Invalid OTP'], 400);

        $user->password = Hash::make($request->password);
        $user->password_reset_otp = null;
        $user->save();

        return response()->json(['message' => 'Password reset successfully']);
    }
}
