<?php

namespace App\Console\Commands\MigrationCommands;

use App\Services\MigrationScripts\OrderMigrationService;
use App\Services\MigrationScripts\PurchaseInvoiceInventoryMigrationService;
use Illuminate\Console\Command;

class MigrateOrdersInventoryCommand extends Command
{
    protected $signature = 'inventory:migrate-from-orders';
    protected $description = 'Migrate inventory transactions from orders';

    public function handle()
    {
        $this->info('Starting orders inventory transaction migration...');
        OrderMigrationService::createInventoryTransactionOrders();
        $this->info('Inventory orders transaction migration completed.');
    }
}
