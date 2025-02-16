<?php

namespace App\Console\Commands\MigrationCommands;

use App\Services\MigrationScripts\ProductMigrationService;
use Illuminate\Console\Command;

class MigratePackageSizeUnitPricesCommand extends Command
{
    protected $signature = 'unit_prices:migrate-from-unit-prices';
    protected $description = 'Migration Unit Prices';

    public function handle()
    {
        $this->info('Starting unit prices migration...');
        ProductMigrationService::updatePackageSizeAndOrder();
        $this->info('Unit prices migration completed.');
    }
}
