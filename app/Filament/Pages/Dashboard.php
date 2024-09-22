<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\CircularWidget;

class Dashboard extends \Filament\Pages\Dashboard

{

    public function getWidgets(): array
    {
        return [
            CircularWidget::class,
        ];
    }
}
