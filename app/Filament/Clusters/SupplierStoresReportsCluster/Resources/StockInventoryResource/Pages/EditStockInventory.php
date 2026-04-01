<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource\Pages;

use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource;
use App\Models\InventoryTransaction;
use App\Models\StockAdjustmentDetail;
use App\Models\StockInventory;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\DB;
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
                ->visible(fn() => (bool) $this->record?->finalized && isSuperAdmin())
                ->action(function () {
                    DB::beginTransaction();
                    try {
                        $inventory = $this->record;

                        // 1. Get all StockAdjustmentDetails linked to this inventory
                        $adjDetails = StockAdjustmentDetail::withTrashed()
                            ->where('source_id', $inventory->id)
                            ->where('source_type', StockInventory::class)
                            ->get();

                        foreach ($adjDetails as $adjDetail) {
                            // 2. Force-delete all InventoryTransactions linked to each detail
                            InventoryTransaction::withTrashed()
                                ->where('transactionable_id', $adjDetail->id)
                                ->where('transactionable_type', StockAdjustmentDetail::class)
                                ->delete();

                            // 3. Force-delete the StockAdjustmentDetail itself
                            $adjDetail->delete();
                        }

                        // 4. Reset is_adjustmented and difference on all inventory details
                        $inventory->details()->update([
                            'is_adjustmented' => false,
                            'difference'      => 0,
                        ]);

                        // 5. Reopen the inventory
                        $inventory->update(['finalized' => false]);

                        DB::commit();

                        Notification::make()
                            ->title('Rollback Successful')
                            ->body('The inventory has been reopened and all adjustments have been removed.')
                            ->success()
                            ->send();

                        $this->redirect(static::getResource()::getUrl('edit', ['record' => $inventory]));
                    } catch (Throwable $th) {
                        DB::rollBack();
                        Notification::make()
                            ->title('Rollback Failed')
                            ->body($th->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    public function getTitle(): string | Htmlable
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
                ->disabled(fn() => !($this->data['edit_enabled'] ?? false))
                ->hidden()
                ->tooltip('Enable editing first to save changes.'),
            $this->getCancelFormAction()->hidden(),
        ];
    }
}