<?php

namespace App\Filament\Traits;

use App\Filament\Pages\InventoryReportLinks;
use Filament\Actions\Action;

trait HasBackButtonAction
{

    protected function getHeaderActions(): array
    {
        return [Action::make('back_to_dashboard')
            ->label(__('Back'))
            ->url(InventoryReportLinks::getUrl())
            ->color('gray')
            ->icon('heroicon-o-arrow-left')];
    }
    protected function getBackToDashboardAction(): Action
    {
        return Action::make('back_to_dashboard')
            ->label(__('Back'))
            ->url(InventoryReportLinks::getUrl())
            ->color('gray')
            ->icon('heroicon-o-arrow-left');
    }
}
