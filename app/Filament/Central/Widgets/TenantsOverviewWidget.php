<?php

namespace App\Filament\Central\Widgets;

use App\Models\CustomTenantModel;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TenantsOverviewWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $totalTenants = CustomTenantModel::count();
        $activeTenants = CustomTenantModel::where('active', true)->count();
        $inactiveTenants = $totalTenants - $activeTenants;
        $dailyBackupCount = CustomTenantModel::forDailyBackup()->count();

        return [
            Stat::make('Total Tenants', (string) $totalTenants)
                ->description('All registered tenants on landlord')
                ->descriptionIcon(Heroicon::BuildingOffice2)
                ->color('primary'),

            Stat::make('Active Tenants', (string) $activeTenants)
                ->description("{$inactiveTenants} inactive")
                ->descriptionIcon(Heroicon::CheckCircle)
                ->color('success'),

            Stat::make('Daily Backups Enabled', (string) $dailyBackupCount)
                ->description('Active tenants scheduled at 04:00')
                ->descriptionIcon(Heroicon::CloudArrowUp)
                ->color('info'),
        ];
    }
}
