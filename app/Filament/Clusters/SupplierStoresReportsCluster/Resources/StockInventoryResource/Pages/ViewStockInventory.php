<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource\Pages;

use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;
use Filament\Actions\Action;
use App\Exports\StockInventoryDetailsExport;
use Maatwebsite\Excel\Facades\Excel;

class ViewStockInventory extends ViewRecord
{
    protected static string $resource = StockInventoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_excel')
                ->label('Export to Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function () {
                    $record = $this->getRecord();
                    $filename = 'stock_inventory_details_' . $record->id . '_' . now()->format('Y_m_d_H_i_s') . '.xlsx';
                    return Excel::download(new StockInventoryDetailsExport($record), $filename);
                }),
        ];
    }
}
