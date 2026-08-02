<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $user = auth()->user();
            
            if ($user->active == 0) {
                abort(403, 'Your account is inactive. Please contact the administrator.');
            }

            if ($user->employee && $user->employee->pendingTerminationRequest()->exists()) {
                abort(403, 'Your account is pending termination. Please contact the administrator.');
            }
        }

        return $next($request);
    }
}
