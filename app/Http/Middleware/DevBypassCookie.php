<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DevBypassCookie
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $secret = config('multitenancy.dev_bypass_secret');

        if (
            is_string($secret)
            && $secret !== ''
            && is_string($request->query('dev'))
            && hash_equals($secret, $request->query('dev'))
        ) {
            $response->cookie('dev_bypass', $secret, 60 * 24); // 24 hours
        }

        return $response;
    }
}
