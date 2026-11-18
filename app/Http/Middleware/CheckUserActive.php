<?php

namespace App\Http\Middleware;

use App\Modules\HR\Employee\Contracts\UserSessionRevokerInterface;
use App\Modules\HR\Employee\Services\AuthEligibilityService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckUserActive
{
    public function __construct(
        private readonly AuthEligibilityService $eligibilityService,
        private readonly UserSessionRevokerInterface $sessionRevoker,
    ) {}

    /**
     * Handle an incoming request for both Web/Filament and Passport API guards.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user() ?? auth('api')->user() ?? auth('web')->user();

        if ($user) {
            $blockReason = $this->eligibilityService->getBlockReason($user);

            if ($blockReason !== null) {
                $this->sessionRevoker->revokeForUser((int) $user->id, $blockReason);

                if (Auth::guard('web')->check()) {
                    Auth::guard('web')->logout();
                    if ($request->hasSession()) {
                        $request->session()->invalidate();
                        $request->session()->regenerateToken();
                    }
                }

                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'success' => false,
                        'error' => $blockReason,
                        'message' => $blockReason,
                    ], 403);
                }

                abort(403, $blockReason);
            }
        }

        return $next($request);
    }
}
