<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\CircularWidget;
use App\Filament\Widgets\EmployeeSearchWidget;
use App\Filament\Widgets\TaskWidget;

class Dashboard extends \Filament\Pages\Dashboard

{
    public function getColumns(): int | string | array
    {
        return 2;
    }
    public function getWidgets(): array
    {
        
        return [
            CircularWidget::class,
            TaskWidget::class,
            EmployeeSearchWidget::class,
        ];
    }
}
