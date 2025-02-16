<?php

namespace App\Console\Commands\MigrationCommands;

use App\Services\MigrationScripts\PurchaseInvoiceInventoryMigrationService;
use Illuminate\Console\Command;

class MigrateInventoryPurchaseCommand extends Command
{
    protected $signature = 'inventory:migrate-from-purchases';
    protected $description = 'Migrate inventory transactions from purchase invoices';

    public function handle()
    {
        $this->info('Starting inventory transaction migration...');
        PurchaseInvoiceInventoryMigrationService::migrateInventoryTransactions();
        $this->info('Inventory transaction migration completed.');
    }
}
