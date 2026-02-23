<?php

namespace App\Services\Auth;

use App\Mail\MailableOtp;
use App\Models\EmailOtp;
use Illuminate\Support\Facades\Mail;

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
}
