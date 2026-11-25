<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\CentralSetting;
use App\Models\CustomTenantModel;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class SuspendOverdueTenantsCommand extends Command
{
    protected $signature = 'tenants:suspend-overdue';

    protected $description = 'Deactivate active tenants that have unpaid overdue invoices when auto-suspension is enabled';

    public function handle(): int
    {
        if (! CentralSetting::shouldSuspendTenantOnOverdueInvoice()) {
            $this->info('Automatic tenant suspension for overdue invoices is disabled.');

            return self::SUCCESS;
        }

        $deactivatedCount = CustomTenantModel::query()
            ->where('active', true)
            ->whereHas('invoices', fn (Builder $query): Builder => $query->overdue())
            ->update([
                'active' => false,
            ]);

        if ($deactivatedCount > 0) {
            Log::info('Deactivated tenants with unpaid overdue invoices.', [
                'deactivated_count' => $deactivatedCount,
                'date' => today()->toDateString(),
            ]);
        }

        $this->info("Deactivated {$deactivatedCount} tenant(s) with unpaid overdue invoices.");

        return self::SUCCESS;
    }
}
