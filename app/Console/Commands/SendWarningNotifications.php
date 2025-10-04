<?php

namespace App\Console\Commands;

use App\Services\Warnings\NotificationOrchestrator;
use Illuminate\Console\Command;

class SendWarningNotifications extends Command
{
    protected $signature = 'notifications:warning
                            {--user= : Target specific user ID or email (optional, for testing)}
                            {--limit=100 : Max users to notify per connection}';

    protected $description = 'Send warning notifications on CENTRAL first, then ALL tenants.';

    public function handle(NotificationOrchestrator $runner): int
    {
        $options = [
            'user'  => $this->option('user'),
            'limit' => (int)($this->option('limit') ?: 100),
        ];

        $this->info('=== CENTRAL ===');
        [$sC, $fC] = $runner->runOnCentral($options);
        $this->info("Central: sent={$sC}, failed={$fC}");

        $this->info('=== TENANTS ===');
        [$sT, $fT] = $runner->runOnTenants($options);
        $this->info("Tenants: sent={$sT}, failed_items={$fT}");

        $this->info("TOTAL: sent=" . ($sC + $sT) . ", failed_items=" . ($fC + $fT));
        event(new \App\Events\WarningNotificationsUpdated([]));

        return ($fC + $fT) > 0 ? self::FAILURE : self::SUCCESS;
    }
}
