<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Config;

class SetTenantAppUrl
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();

        // You could fetch domain from DB here (Tenant::where('domain', $host)->first())
        // For example purposes, we'll set it directly:
        $appUrl = 'https://' . $host;

        // Override Laravel app.url
        Config::set('app.url', $appUrl);
        URL::forceRootUrl($appUrl);

        // Force HTTPS if needed
        if ($request->isSecure()) {
            URL::forceScheme('https');
        }

        return $next($request);
        return $next($request);
    }
}
