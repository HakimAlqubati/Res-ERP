<?php
namespace App\Filament\Traits;

use Filament\Actions\Action;

trait HasBackButtonAction
{

    protected function getHeaderActions(): array
    {
        $from = request('from_url');

        $backUrl = match ($from) {
            'branch-resellers' => \App\Filament\Resources\BranchResellerResource::getUrl('index'),
            default => \App\Filament\Pages\InventoryReportLinks::getUrl(),
        };

        return [Action::make('back_to_dashboard')
                ->label(__('Back'))
            // ->url(InventoryReportLinks::getUrl())
                ->url($backUrl)
                ->color('gray')
                ->icon('heroicon-o-arrow-left')];
    }

}