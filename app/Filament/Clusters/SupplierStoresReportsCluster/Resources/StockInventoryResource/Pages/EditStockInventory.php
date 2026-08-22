<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource\Pages;

use App\Exports\StockInventoryDetailsExport;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource;
use App\Models\FinancialTransaction;
use App\Models\InventoryTransaction;
use App\Models\StockAdjustmentDetail;
use App\Models\StockInventory;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class EditStockInventory extends EditRecord
{
    protected static string $resource = StockInventoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('rollbackFinalize')
                ->label('Rollback Finalize')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Rollback Inventory Finalization')
                ->modalDescription('This will permanently delete all stock adjustments and inventory transactions created during this stocktake, and reopen the inventory for editing. This action cannot be undone.')
                ->modalSubmitActionLabel('Yes, Rollback')
                ->visible(fn () => (bool) $this->record?->finalized && isHakim())
                ->action(fn ()=> $this->rollbackInventoryFinalize() )
            // ->hidden()
            ,
            Action::make('export_excel')
                ->label('Export to Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function () {
                    $record = $this->getRecord();
                    $filename = 'stock_inventory_details_'.$record->id.'_'.now()->format('Y_m_d_H_i_s').'.xlsx';

                    return Excel::download(new StockInventoryDetailsExport($record), $filename);
                }),
        ];
    }

    public function getTitle(): string|Htmlable
    {
        return 'Finalize';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->disabled(fn () => (! (isSystemManager() || isSuperAdmin()) || $this->record->finalized))
                ->hidden(fn () => (! (isSystemManager() || isSuperAdmin()) || $this->record->finalized)),
            $this->getCancelFormAction()->hidden(),
        ];
    }

    protected function rollbackInventoryFinalize(): void
    {
        abort_unless(isHakim(), 403);

        DB::beginTransaction();
        try {
            $inventory = $this->record;

            // 1. Get IDs of all StockAdjustmentDetails linked to this inventory
            $adjDetailIds = StockAdjustmentDetail::withTrashed()
                ->where('source_id', $inventory->id)
                ->where('source_type', StockInventory::class)
                ->pluck('id');

            if ($adjDetailIds->isNotEmpty()) {
                // 2. Safety check: Ensure no downstream transactions have consumed from inbound adjustments
                $inboundTxIds = InventoryTransaction::withTrashed()
                    ->whereIn('transactionable_id', $adjDetailIds)
                    ->where('transactionable_type', StockAdjustmentDetail::class)
                    ->where('movement_type', InventoryTransaction::MOVEMENT_IN)
                    ->pluck('id');

                if ($inboundTxIds->isNotEmpty()) {
                    $consumedOutTransactions = InventoryTransaction::whereIn('source_transaction_id', $inboundTxIds)
                        ->where('movement_type', InventoryTransaction::MOVEMENT_OUT)
                        ->with('product:id,name,code')
                        ->get();

                    if ($consumedOutTransactions->isNotEmpty()) {
                        $productNames = $consumedOutTransactions
                            ->map(fn ($tx) => $tx->product ? "{$tx->product->code} - {$tx->product->name}" : "Product #{$tx->product_id}")
                            ->unique()
                            ->implode(', ');

                        throw new \Exception("Cannot rollback: Added stock has already been used for ({$productNames}).");
                    }
                }

                // 3. Batch force-delete InventoryTransactions linked to these adjustments
                InventoryTransaction::withTrashed()
                    ->whereIn('transactionable_id', $adjDetailIds)
                    ->where('transactionable_type', StockAdjustmentDetail::class)
                    ->forceDelete();

                // 4. Batch force-delete the StockAdjustmentDetails
                StockAdjustmentDetail::withTrashed()
                    ->whereIn('id', $adjDetailIds)
                    ->forceDelete();
            }

            // 5. Clean up any Closing/Opening Stock Financial Transactions created for this inventory
            FinancialTransaction::withTrashed()
                ->where('reference_type', StockInventory::class)
                ->where('reference_id', $inventory->id)
                ->forceDelete();

            // 6. Reset only the is_adjustmented flag on details (preserving the calculated difference)
            $inventory->details()->update([
                'is_adjustmented' => false,
            ]);

            // 7. Reopen the inventory
            $inventory->update(['finalized' => false]);

            DB::commit();

            Notification::make()
                ->title('Rollback Successful')
                ->body('The inventory has been reopened and all adjustments and financial entries have been removed.')
                ->success()
                ->send();

            $this->redirect(static::getResource()::getUrl('edit', ['record' => $inventory]));
        } catch (Throwable $th) {
            DB::rollBack();

            Log::error('Stock Inventory Rollback Failed: ' . $th->getMessage(), [
                'inventory_id' => $this->record?->id,
                'exception'    => $th,
            ]);

            Notification::make()
                ->title('Rollback Failed')
                ->body($th->getMessage())
                ->danger()
                ->send();
        }
    }
}
