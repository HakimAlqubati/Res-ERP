<?php

namespace App\Filament\Resources\StockReportResource\Pages;

use App\Filament\Resources\StockReportResource;
use App\Models\Store;
use App\Models\Supplier;
use Filament\Forms\Components\Builder;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Filters\SelectFilter;

class ListStockReports extends ListRecords
{
    protected static string $resource = StockReportResource::class;
    protected static string $view = 'filament.pages.stock-report.stock-report';
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getTableFilters(): array
    {
        return [
            SelectFilter::make("stock_id")
                ->label(__('lang.store'))
                ->query(function (Builder $q, $data) {
                    return $q;
                })->options(Store::get()->pluck('name', 'id')),
            SelectFilter::make("supplier_id")
                ->label(__('lang.supplier'))
                ->query(function (Builder $q, $data) {
                    return $q;
                })->options(Supplier::get()->pluck('name', 'id')),
        ];
    }


    protected function getViewData(): array
    {
        $purchase_invoice = null;
        $stock_data = ['1', '2', '3'];
        return [
            'stock_data' => $stock_data,
        ];
    }
}
