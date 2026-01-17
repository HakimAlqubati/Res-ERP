<?php

namespace App\Filament\Clusters\FinanceFormattingCluster\Resources\Accounts\Pages;

use App\Filament\Clusters\FinanceFormattingCluster\Resources\Accounts\AccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListAccounts extends ListRecords
{
    protected static string $resource = AccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('tree')
                ->label(__('Show Tree'))
                ->icon(Heroicon::OutlinedRectangleGroup)
                ->color('success')
                // ->url(fn() => AccountResource::getUrl('tree')),
                ->url(fn() => route('accounting.test.tree'))
                ->openUrlInNewTab()
                ,
            CreateAction::make()->icon(Heroicon::OutlinedPlusCircle),
        ];
    }
}
