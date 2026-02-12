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

        // dd($host, $centralDomain, $request->all(),$subdomain);
        if (
            $host === $centralDomain || $host === '127.0.0.1'
            || $host === 'workbench.test'
            || $host === '192.168.8.149'
        ) {
            return null;
        }

        // Developer bypass: use ?dev=hakim once, then cookie persists for Livewire requests
        $isDev = $request->query('dev') === 'hakim' || $request->cookie('dev_bypass') === 'hakim';

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
