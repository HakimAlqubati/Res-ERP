<?php

namespace App\Filament\Dev\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class SystemHealthWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $dbStatus = 'OK';
        $dbColor = 'success';
        $dbIcon = 'heroicon-m-check-circle';

        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            $dbStatus = 'Error';
            $dbColor = 'danger';
            $dbIcon = 'heroicon-m-x-circle';
        }

        return [
            Stat::make('Database Connection', $dbStatus)
                ->description(config('database.default'))
                ->descriptionIcon($dbIcon)
                ->color($dbColor),

            Stat::make('Environment', app()->environment())
                ->description('Debug Mode: ' . (config('app.debug') ? 'Enabled' : 'Disabled'))
                ->color(app()->environment('production') ? 'danger' : 'success'),

            Stat::make('Laravel Version', app()->version())
                ->description('PHP ' . phpversion())
                ->color('primary'),
        ];
    }
}
