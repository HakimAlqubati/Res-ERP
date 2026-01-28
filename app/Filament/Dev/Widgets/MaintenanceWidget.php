<?php

namespace App\Filament\Dev\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Artisan;
use Filament\Notifications\Notification;

class MaintenanceWidget extends Widget
{
    protected   string $view = 'filament.dev.widgets.maintenance-widget';

    // protected static int | string | array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function optimize()
    {
        Artisan::call('optimize:clear');
        Notification::make()->title('Optimization & Cache Cleared')->success()->send();
    }

    public function clearView()
    {
        Artisan::call('view:clear');
        Notification::make()->title('Views Cleared')->success()->send();
    }

    public function clearConfig()
    {
        Artisan::call('config:clear');
        Notification::make()->title('Config Cleared')->success()->send();
    }
}
