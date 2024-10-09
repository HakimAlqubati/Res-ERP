<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {
        // Check if the user is authenticated
        if (!Auth::check() || !(isSystemManager() || isSuperAdmin() || isBranchManager())) {
            // If the request expects JSON, return a custom message
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthorized access. Please log in to continue.'], 401);
            }
            
            // Otherwise, redirect to the custom login page
            return redirect('/admin/login');
        }

        return $next($request);
    }
}
