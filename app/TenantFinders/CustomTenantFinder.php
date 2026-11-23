<?php

namespace App\TenantFinders;

use App\Models\CustomTenantModel;
use Illuminate\Http\Request;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\TenantFinder\TenantFinder;

class CustomTenantFinder extends TenantFinder
{
    public function findForRequest(Request $request): ?IsTenant
    {
        $host = strtolower($request->getHost());

        if (CustomTenantModel::isCentralHost($host)) {
            return null;
        }

        $tenant = app(IsTenant::class)::where('domain', $host)->first();

        if (! $tenant) {
            abort(503, 'This tenant is not found.');
        }

        if (! $tenant->active && ! $this->hasValidDevBypass($request)) {
            abort(403, 'This tenant is inactive.');
        }

        return $tenant;
    }

    protected function hasValidDevBypass(Request $request): bool
    {
        $secret = config('multitenancy.dev_bypass_secret');

        if (! is_string($secret) || $secret === '') {
            return false;
        }

        $candidate = (string) ($request->query('dev') ?? $request->cookie('dev_bypass') ?? '');

        return $candidate !== '' && hash_equals($secret, $candidate);
    }
}
