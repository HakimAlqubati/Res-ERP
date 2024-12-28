<?php

namespace App\Traits;

use Spatie\Multitenancy\Models\Concerns\UsesLandlordConnection;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

trait DynamicConnection
{
    /**
     * Determine the connection name dynamically.
     *
     * @return string|null
     */

    use UsesLandlordConnection, UsesTenantConnection {
        UsesLandlordConnection::getConnectionName insteadof UsesTenantConnection;
        UsesTenantConnection::getConnectionName as getTenantConnectionName;
    }

    public function getConnectionName()
    {
        $explodeHost = explode('.', request()->getHost());

        $count = count($explodeHost);
        if (
            env('APP_ENV') === 'local' && $count === 2 ||
            env('APP_ENV') === 'production' && $count === 3
        ) {
            return $this->getTenantConnectionName();
        }

        return 'landlord';
    }

}
