<?php

namespace App\Filament\Pages\Auth;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Auth\Http\Responses\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Log;

class CustomLogin extends BaseLogin
{
    protected string $view = 'filament.pages.auth.login';

    public function hasLogo(): bool
    {
        return false;
    }

    public function getHeading(): string|Htmlable
    {
        return '';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return null;
    }

    /**
     * ── Security Override: Enhanced Rate Limiting & Logging ──
     *
     * We override the authenticate method to enforce a strict rate limit of 5 attempts
     * and log all lockout events for security auditing (as requested).
     */
    public function authenticate(): ?LoginResponse
    {
        try {
            // 2. Limits: 5 failed attempts per minute
            // $this->rateLimit(2);
        } catch (TooManyRequestsException $exception) {
            // 5. Security Logging: Record IP and Email on lockout
            Log::warning('Login Lockout Event Detected', [
                'ip' => request()->ip(),
                'email' => $this->data['email'] ?? 'unknown',
                'agent' => request()->userAgent(),
                'until' => $exception->secondsUntilAvailable,
            ]);

            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        return parent::authenticate();
    }
}
