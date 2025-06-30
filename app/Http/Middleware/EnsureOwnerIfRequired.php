<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureOwnerIfRequired
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        // فقط افحص إذا تم تمرير require_owner = true
        if ($request->boolean('require_owner')) {
            $user = Auth::user();

            if($user){
                dd($user);
                
            }
            // تأكد من تسجيل الدخول و أن المستخدم ليس owner
            if (!$user || !isOwner()) {
                Auth::logout(); // تسجيل الخروج الإجباري
                return response()->json(['error' => 'Access denied. Owner only.'], 403);
            }
        }

        return $next($request);
    }
}