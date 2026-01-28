<?php

namespace App\Filament\Dev\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class DevDashboard extends BaseDashboard
{
    protected static ?string $title = 'Developer Console';

    protected static ?string $navigationLabel = 'Console';


    public function getWidgets(): array
    {
        return [
            \App\Filament\Dev\Widgets\SystemHealthWidget::class,
            \App\Filament\Dev\Widgets\MaintenanceWidget::class,
        ];
    }
}
