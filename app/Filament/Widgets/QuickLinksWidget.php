<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Contracts\View\View;

class QuickLinksWidget extends Widget
{
    protected string $view = 'filament.widgets.quick-links-widget';

    protected int|string|array $columnSpan = 'full';

    /**
     * Toggle to show/hide count badges
     * Set to false to improve performance
     */
    public bool $showCounts = true;

    public function render(): View
    {
        return view($this->view, [
            'showCounts' => $this->showCounts,
        ]);
    }
}
