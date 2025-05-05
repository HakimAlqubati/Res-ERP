<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class QuickLinksWidget extends Widget
{
    protected static string $view = 'filament.widgets.quick-links-widget';

    protected int|string|array $columnSpan = 'full';
}
