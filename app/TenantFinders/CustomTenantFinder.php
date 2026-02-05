<?php

namespace App\TenantFinders;

use Illuminate\Http\Request;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\TenantFinder\TenantFinder;

class CustomTenantFinder extends TenantFinder
{
    public function findForRequest(Request $request): ?IsTenant
    {
        $host = $request->getHost();
        $subdomain = $host;
        $centralDomain = env('CENTRAL_DOMAIN', 'localhost');

        if ($host === $centralDomain || $host === '127.0.0.1') {
            return null;
        }

        // Developer bypass: add ?dev=hakim to URL (auth not available at this stage)
        $isDev = $request->query('dev') === 'hakim';


        $query = app(IsTenant::class)::where('domain', $subdomain);

        if (!$isDev) {
            $query->where('active', 1);
        }

        $tenant = $query->first();

        if ($tenant) {
            return $tenant;
        }

        abort(403, 'This tenant is inactive.');
    }
}
