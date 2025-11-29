<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Actions\Action;
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
            CreateAction::make()
                ->icon('heroicon-o-plus-circle')
                ->label('New Stocktake'),
            Action::make('print')

                ->label('Print Stocktake Template')
                ->schema([
                    Select::make('category_id')->label('Category')->columnSpanFull()
                        ->options(Category::active()
                            ->notForPos()
                            ->pluck('name', 'id'))
                        ->placeholder('All Categories')->searchable()
                ])
                ->action(function ($data) {

                    return redirect('/printStock?' . 'category_id=' . $data['category_id']);
                })
                // ->openUrlInNewTab()
                // ->url(fn() => url('/printStock'))
                ->color('success')
                ->icon('heroicon-o-printer'),

        ];
    }
    // Add this new function inside the ListStockInventories class
    public function getRecordsWithDetails(): array
    {
        // Get the records currently displayed in the table
        $records = $this->getFilteredTableQuery()->get();

        // Return an array mapping the record ID to its details_count
        return $records->mapWithKeys(function ($record) {
            return [$record->id => $record->details_count];
        })->toArray();
    }
}
