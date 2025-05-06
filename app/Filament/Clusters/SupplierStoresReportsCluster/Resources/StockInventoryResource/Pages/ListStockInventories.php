<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource\Pages;

use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource;
use App\Models\Category;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;

class ListStockInventories extends ListRecords
{
    protected static string $resource = StockInventoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
            ->icon('heroicon-o-plus-circle')
            ->label('New Stocktake'),
            Actions\Action::make('print')

                ->label('Print Stocktake Template')
                ->form([
                    Select::make('category_id')->label('Category')->columnSpanFull()
                        ->options(Category::active()->pluck('name', 'id'))
                        ->placeholder('All Categories')->searchable()
                ])
                ->action(function ($data) {

                    return redirect('/printStock?'.'category_id='.$data['category_id']);
                })
                // ->openUrlInNewTab()
                // ->url(fn() => url('/printStock'))
                ->color('success')
                ->icon('heroicon-o-printer'),

        ];
    }
}
