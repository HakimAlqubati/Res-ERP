<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DevBypassCookie
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // If dev=hakim is in query, set cookie for 30 days
        if ($request->query('dev') === 'hakim') {
            $response->cookie('dev_bypass', 'hakim', 60 * 24 * 30); // 30 days
        }

        return $response;
    }
}
