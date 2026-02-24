<?php

namespace App\Services\Auth;

use App\Mail\MailableOtp;
use App\Models\EmailOtp;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class EmailOtpService
{
    /**
     * Generate and send OTP to the given email address.
     *
     * @param string $email
     * @return string
     */
    public function sendOtp(string $email): string
    {
        $otp = (string) rand(100000, 999999);

        // Remove old OTP records for the same email to avoid conflicts
        EmailOtp::where('email', $email)->delete();

        // Save new OTP record
        EmailOtp::create([
            'email' => $email,
            'otp' => $otp,
            'expires_at' => now()->addMinutes(5),
        ]);

        // Send OTP via email
        Mail::to($email)->send(new MailableOtp($otp, $email));

        return $otp;
    }

    /**
     * Validate the given OTP for the email address.
     *
     * @param string $email
     * @param string $otp
     * @return bool
     */
    public function isValidOtp(string $email, string $otp): bool
    {
        $otpRecord = EmailOtp::where('email', $email)
            ->where('otp', $otp)
            ->latest()
            ->first();

        if (!$otpRecord || $otpRecord->isExpired()) {
            return false;
        }

        // Delete the OTP after successful validation
        $otpRecord->delete();

        return true;
    }

    /**
     * Verify OTP and generate a one-time reset token for password change.
     *
     * @param string $email
     * @param string $otp
     * @return string|null  Returns reset_token on success, null on failure
     */
    public function verifyOtpAndGenerateResetToken(string $email, string $otp): ?string
    {
        $otpRecord = EmailOtp::where('email', $email)
            ->where('otp', $otp)
            ->latest()
            ->first();

        if (!$otpRecord || $otpRecord->isExpired()) {
            return null;
        }

        $resetToken = Str::random(64);

        // Update the record: store reset token
        $otpRecord->update([
            'reset_token' => $resetToken,
            'reset_token_expires_at' => now()->addMinutes(10),
        ]);

        return $resetToken;
    }

    /**
     * Validate a reset token for password change.
     *
     * @param string $email
     * @param string $resetToken
     * @return bool
     */
    public function isValidResetToken(string $email, string $resetToken): bool
    {
        $record = EmailOtp::where('email', $email)
            ->where('reset_token', $resetToken)
            ->first();

        if (!$record || now()->greaterThan($record->reset_token_expires_at)) {
            return false;
        }

        // Delete the record after successful validation (one-time use)
        $record->delete();

        return true;
    }
}
