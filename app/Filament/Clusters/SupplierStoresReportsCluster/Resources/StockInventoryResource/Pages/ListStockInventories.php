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
            Actions\CreateAction::make()->label('New Stock Take'),
            Actions\Action::make('print')

                ->label('Print Takes Paper')
                ->form([
                    Select::make('category_id')->label('Category')->columnSpanFull()
                        ->options(Category::active()->pluck('name', 'id'))
                ])
                ->action(function ($data) {

                    return redirect('/printStock?'.'category_id='.$data['category_id']);
                })
                // ->openUrlInNewTab()
                // ->url(fn() => url('/printStock'))
                ->icon('heroicon-o-rectangle-stack'),

        ];
    }
}
