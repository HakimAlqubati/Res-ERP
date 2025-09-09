<?php

namespace App\Console\Commands;

use App\Filament\Resources\TenantResource;
use App\Models\CustomTenantModel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TenantBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:backup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate backups for all active tenants';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Log::info('🚀 tenant:backup started at ' . now());

        $tenants = CustomTenantModel::where('active', 1)->get();

        foreach ($tenants as $tenant) {
            try {
                TenantResource::generateTenantBackup($tenant);
                // Log::info('Backup successful for tenant: ' . $tenant->name);
            } catch (\Throwable $e) {
                // Log::error('Backup failed for tenant: ' . $tenant->name, [
                //     'error' => $e->getMessage(),
                // ]);
            }
        }
        // Log::info('🏁 tenant:backup finished at ' . now());
        return self::SUCCESS;
    }
}
