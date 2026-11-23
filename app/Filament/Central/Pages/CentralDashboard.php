<?php

namespace App\Filament\Central\Pages;

use App\Filament\Central\Widgets\TenantsOverviewWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class CentralDashboard extends BaseDashboard
{
    protected static ?string $title = 'Central Control Panel';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = 1;

    public function getWidgets(): array
    {
        return [
            TenantsOverviewWidget::class,
        ];
    }
}
