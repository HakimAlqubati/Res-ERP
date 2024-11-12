<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\CircularWidget;
use App\Filament\Widgets\TaskWidget;

class Dashboard extends \Filament\Pages\Dashboard

{

    public function getWidgets(): array
    {
        return [
            CircularWidget::class,
            TaskWidget::class,
        ];
    }
}
