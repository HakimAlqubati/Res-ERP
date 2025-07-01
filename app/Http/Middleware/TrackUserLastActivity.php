<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class TrackUserLastActivity
{
    public function handle($request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();

            // ✅ نحدّث فقط إذا مرّ أكثر من 1 دقيقة
            if (!$user->last_seen_at || now()->diffInMinutes($user->last_seen_at) >= 1) {
                $user->last_seen_at = now();
                $user->saveQuietly(); // تحديث بدون أحداث/Listeners
            }
        }

        return $next($request);
    }
}