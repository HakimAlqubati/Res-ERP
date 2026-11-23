<?php

namespace App\Http\Middleware;

use App\Models\CustomTenantModel;
use Closure;
use Illuminate\Http\Request;
use Spatie\Multitenancy\Contracts\IsTenant;
use Symfony\Component\HttpFoundation\Response;

class EnsureCentralDomain
{
    /**
     * Allow access strictly on configured central (landlord) hosts with no active tenant context.
     * Returns 404 on tenant domains to prevent panel discovery and cross-tenant access.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (app(IsTenant::class)::checkCurrent() || ! CustomTenantModel::isCentralHost($request->getHost())) {
            abort(404);
        }

        return $next($request);
    }
}
