<?php

namespace App\Filament\Dev\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\File;

class LatestLogsWidget extends Widget
{
    protected   string $view = 'filament.dev.widgets.latest-logs-widget';

    // protected static int | string | array $columnSpan = 'full';

    protected static ?int $sort = 3;

    public function getLogs(): array
    {
        return [];
    }
}
