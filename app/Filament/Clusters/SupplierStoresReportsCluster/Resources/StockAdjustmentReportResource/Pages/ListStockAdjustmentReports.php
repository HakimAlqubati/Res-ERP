<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockAdjustmentReportResource\Pages;

use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockAdjustmentReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Model;

class ListStockAdjustmentReports extends ListRecords
{
    use \App\Filament\Traits\HasBackButtonAction;
    protected static string $resource = StockAdjustmentReportResource::class;


    public function getTableRecordKey(Model $record): string
    {
        $attributes = $record->getAttributes();
        return $attributes['product_id'];
    }
    // protected function getHeaderActions(): array
    // {
    //     return [
    //         // Actions\CreateAction::make(),
    //     ];
    // }
}
