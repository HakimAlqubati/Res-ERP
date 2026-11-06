<?php

namespace App\Filament\Tables\Actions;

use Filament\Actions\Action;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;

class RefreshAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'refresh';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('lang.refresh'))
            ->icon(Heroicon::ArrowPath)
            // ->color('primary')
            ->color(Color::Gray)
            ->size(Size::Medium)
            ->action(function () {
                showSuccessNotifiMessage(__('lang.refreshed'));
            });
    }
}
