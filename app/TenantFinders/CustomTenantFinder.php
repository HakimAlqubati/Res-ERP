<?php

namespace App\TenantFinders;

use Illuminate\Http\Request;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\TenantFinder\TenantFinder;

class CustomTenantFinder extends TenantFinder
{
    public function findForRequest(Request $request): ?IsTenant
    {    // Implement your custom logic to identify the tenant
        // For example, using a subdomain:
        $host = $request->getHost();
        $subdomain = $host;
        $centralDomain = env('CENTRAL_DOMAIN', 'localhost');
        // dd($centralDomain,$host,$host === $centralDomain);
        if ($host === $centralDomain) {
            return null;
        }
        $tenant = app(IsTenant::class)::where('domain', $subdomain)
            ->where('active', 1)
            ->first();
        if ($tenant) {
            return $tenant;
        }
        abort(403, 'This tenant account is inactive.');
        return $tenant;
    }
}
