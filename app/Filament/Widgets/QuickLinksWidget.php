<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Contracts\View\View;

class QuickLinksWidget extends Widget
{
    protected string $view = 'filament.widgets.quick-links-widget';

    protected int|string|array $columnSpan = 'full';

    public function render(): View
    {
        return view($this->view);
    }
}
