<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\EmailOtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ForgotPasswordController extends Controller
{
    protected EmailOtpService $emailOtpService;

    public function __construct(EmailOtpService $emailOtpService)
    {
        $this->emailOtpService = $emailOtpService;
    }

    /**
     * Send OTP to the user's email.
     */
    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $this->emailOtpService->sendOtp($request->email);

        return response()->json([
            'success' => true,
            'message' => 'OTP sent to your email successfully.',
        ]);
    }

    /**
     * Verify the OTP and generate a reset token.
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string',
        ]);

        $resetToken = $this->emailOtpService->verifyOtpAndGenerateResetToken($request->email, $request->otp);

        if (!$resetToken) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid or expired OTP'
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'OTP verified successfully.',
            'reset_token' => $resetToken,
        ]);
    }

    /**
     * Reset the user's password using the reset token.
     */
    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
            'reset_token' => 'required|string',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        // Verify the reset token matches the email
        if (!$this->emailOtpService->isValidResetToken($request->email, $request->reset_token)) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid or expired reset token. Please verify OTP first.'
            ], 401);
        }

        // Update the user's password
        $user = User::where('email', $request->email)->firstOrFail();

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        // Optional: you can revoke current access tokens here if you want
        // $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password has been reset successfully. You can now login.',
        ]);
    }
}
