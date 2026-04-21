<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\VerifyOtpRequest;
use App\Services\OtpService;
use App\Models\User;
use Illuminate\Http\Request;

class EmailVerificationController extends BaseController
{
    protected $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * Verify Email OTP
     */
    public function verify(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|digits:6',
        ]);

        $user = User::where('email', $request->email)->first();

        try {
            // ✅ استخدمي verifyOtp بدلاً من validateOtp
            $this->otpService->verifyOtp($user, 'email_verification', $request->otp);

            // تحديث حالة التحقق ومسح OTP
            $user->update([
                'is_verified' => true,
                'email_verification_otp' => null,
                'email_verification_otp_expires_at' => null,
            ]);

            // إنشاء token
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Email verified successfully',
                'data' => [
                    'token' => $token,
                    'user' => $user
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Resend OTP
     */
    public function resend(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);

        $user = User::where('email', $request->email)->first();

        try {
            $this->otpService->resendOtp($user, 'email_verification');

            return response()->json([
                'success' => true,
                'message' => 'OTP resent successfully',
                'data' => ['email' => $user->email]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
