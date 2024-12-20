<?php

namespace App\TenantFinders;

use Illuminate\Http\Request;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\TenantFinder\TenantFinder;

class CustomTenantFinder extends TenantFinder
{
    public function findForRequest(Request $request): ?IsTenant
    {
        
        // Implement your custom logic to identify the tenant
        // For example, using a subdomain:
        $host = $request->getHost();
        
        // $subdomain = explode('.', $host)[0];
        // $subdomain ='tenant1.'. $host;
        $subdomain = $host;
        // dd($subdomain);
        // dd(app(IsTenant::class)::where('domain', $subdomain)->first());
        return app(IsTenant::class)::where('domain', $subdomain)->first();
    }
}
