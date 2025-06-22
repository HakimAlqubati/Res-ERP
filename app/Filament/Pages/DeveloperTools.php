<?php

namespace App\Filament\Pages;

use App\Jobs\CopyOrderOutToBranchStoreJob;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;

class DeveloperTools extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationLabel = 'Developer Tools';
    protected static ?string $navigationGroup = '⚙️ Developer';
    protected static string $view = 'filament.pages.developer-tools';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('Rebuild Inventory')
                ->label('♻️ Rebuild Inventory From Sources')
                ->color('gray')
                ->requiresConfirmation()
                ->action(function () {
                    try {
                        dispatch(new \App\Jobs\RebuildInventoryFromSources());
                        showSuccessNotifiMessage('✅ Inventory rebuild job dispatched.');
                    } catch (\Throwable $th) {

                        showWarningNotifiMessage($th->getMessage());
                    }
                }),

            Action::make('FIFO Allocation')
                ->label('📦 FIFO Allocation for All Products')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function () {
                    try {
                        dispatch(new \App\Jobs\AllocateAllProductsFifoJob());

                        showSuccessNotifiMessage('✅ FIFO Allocation command executed successfully.');
                    } catch (\Throwable $th) {
                        showWarningNotifiMessage("❌ Error: " . $th->getMessage());
                    }
                }),


            Action::make('Copy Order OUT to IN')
                ->label('🔄 Copy Order OUT to IN')
                ->color('success')
                ->requiresConfirmation()
                ->action(function () {
                    dispatch(new CopyOrderOutToBranchStoreJob());
                    showSuccessNotifiMessage('done');
                }),


            Action::make('Manufacturing Backfill')
                ->label('⚙️ Manufacturing Backfill (Auto OUT)')
                ->color('danger')
                ->requiresConfirmation()
                ->form([
                    \Filament\Forms\Components\Select::make('store_id')
                        ->label('Store')
                        ->options(\App\Models\Store::active()->get(['id', 'name'])->pluck('name', 'id'))
                        ->required(),
                ])
                ->action(function (array $data) {
                    $storeId = $data['store_id'];
                    dispatch(new \App\Jobs\ManufacturingBackfillJob($storeId));
                    showSuccessNotifiMessage('done');
                }),
            Action::make('Update Product Unit Prices')
                ->label('💰 Update Product Unit Prices')
                ->color('success')

                ->requiresConfirmation()
                ->action(function (array $data) {
                    $tenantId = $data['tenant_id'] ?? null;
                    dispatch(new \App\Jobs\UpdateProductUnitPricesJob($tenantId));
                    showSuccessNotifiMessage('✅ Job dispatched to update product unit prices.');
                }),
        ];
    }
    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }
}
