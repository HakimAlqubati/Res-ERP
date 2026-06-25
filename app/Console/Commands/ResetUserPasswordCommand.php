<?php

namespace App\Console\Commands;

use App\Models\CustomTenantModel;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class ResetUserPasswordCommand extends Command
{
    protected $signature = 'user:reset-password
                            {tenant_id : The ID of the tenant}
                            {email     : The user\'s email address}
                            {password  : The new plain-text password}';

    protected $description = 'Reset a user\'s password inside a specific tenant.';

    public function handle(): int
    {
        $tenantId = $this->argument('tenant_id');
        $email    = $this->argument('email');
        $password = $this->argument('password');

        $tenant = CustomTenantModel::find($tenantId);

        if (! $tenant) {
            $this->error("Tenant [{$tenantId}] not found.");
            return self::FAILURE;
        }

        $tenant->makeCurrent();

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("No user found with email [{$email}] in tenant [{$tenant->name}].");
            \Spatie\Multitenancy\Models\Tenant::forgetCurrent();
            return self::FAILURE;
        }

        $user->password = Hash::make($password);
        $user->save();

        \Spatie\Multitenancy\Models\Tenant::forgetCurrent();

        $this->info("✔ Password updated for [{$email}] in tenant [{$tenant->name}].");
        return self::SUCCESS;
    }
}
