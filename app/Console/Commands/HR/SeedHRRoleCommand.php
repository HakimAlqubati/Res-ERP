<?php

namespace App\Console\Commands\HR;

use App\Models\AppLog;
use App\Models\CustomTenantModel;
use App\Models\UserType;
use Illuminate\Console\Command;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Permission\Models\Role;

class SeedHRRoleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hr:seed-role';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create "HR" role (guard: web) in all tenant databases and link it to the "Middle management" user_type (id=2).';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // If already inside a tenant context, process only that tenant
        if (Tenant::current()) {
            return $this->processForTenant(Tenant::current());
        }

        // Loop through all active tenants
        $tenants = CustomTenantModel::where('active', 1)->get();

        if ($tenants->isEmpty()) {
            $this->warn('No active tenants found.');
            return 0;
        }

        $this->info("Starting HR role seeding for {$tenants->count()} tenant(s).");

        foreach ($tenants as $tenant) {
            $this->line('--------------------------------------------------');
            $this->info("Processing tenant: {$tenant->name}");

            try {
                $tenant->makeCurrent();
                $this->processForTenant($tenant);
            } catch (\Exception $e) {
                $this->error("Error processing tenant [{$tenant->name}]: " . $e->getMessage());

                AppLog::write(
                    message: "Error seeding HR role for tenant [{$tenant->name}]: " . $e->getMessage(),
                    level: AppLog::LEVEL_ERROR,
                    context: 'HR_SEED_ROLE',
                    extra: [
                        'tenant' => $tenant->name,
                        'error'  => $e->getMessage(),
                        'trace'  => $e->getTraceAsString(),
                    ]
                );
            } finally {
                Tenant::forgetCurrent();
            }
        }

        $this->info('All tenants processed.');
        return 0;
    }

    /**
     * Process a single tenant: create the HR role and link it to user_type id=2.
     */
    protected function processForTenant($tenant): int
    {
        // ── 1. Create (or fetch) the HR role ─────────────────────────────────
        $role = Role::firstOrCreate(
            ['id' => 19, 'name' => 'HR', 'guard_name' => 'web'],
        );

        $wasCreated = $role->wasRecentlyCreated;
        $roleAction = $wasCreated ? 'Created' : 'Already exists';

        $this->line("  ✔ Role [HR] — {$roleAction} (id: {$role->id})");

        // ── 2. Update user_type id=2 (Middle management) ─────────────────────
        $userType = UserType::find(2);

        if (! $userType) {
            $this->warn("  ⚠ user_type with id=2 (Middle management) not found in tenant [{$tenant->name}]. Skipping link.");
            return 0;
        }

        $roleIds = $userType->role_ids ?? [];

        if (! in_array($role->id, $roleIds)) {
            $roleIds[] = $role->id;
            $userType->role_ids = $roleIds;
            $userType->save();

            $this->line("  ✔ Linked role [HR] (id: {$role->id}) → user_type [{$userType->name}] (id: 2)");
        } else {
            $this->line("  ✔ Role [HR] already linked to user_type [{$userType->name}] (id: 2). No changes needed.");
        }

        AppLog::write(
            message: "HR role seeded for tenant [{$tenant->name}]. Role id: {$role->id}, action: {$roleAction}.",
            level: AppLog::LEVEL_INFO,
            context: 'HR_SEED_ROLE',
            extra: [
                'tenant'    => $tenant->name,
                'role_id'   => $role->id,
                'action'    => $roleAction,
                'user_type' => $userType->name ?? null,
            ]
        );

        return 0;
    }
}
