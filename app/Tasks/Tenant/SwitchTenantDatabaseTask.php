<?php
namespace App\Tasks\Tenant;
use Illuminate\Support\Facades\DB;
use Spatie\Multitenancy\Tasks\SwitchTenantDatabaseTask as SpatieSwitchTenantDatabaseTask;

class SwitchTenantDatabaseTask extends SpatieSwitchTenantDatabaseTask
{
    protected function setTenantConnectionDatabaseName(?string $databaseName):void 
    {
        parent::setTenantConnectionDatabaseName($databaseName);

        $tenantConnectionName = is_null($databaseName)
            ? $this->landlordDatabaseConnectionName()
            : $this->tenantDatabaseConnectionName();

        DB::setDefaultConnection($tenantConnectionName);
    }
}
