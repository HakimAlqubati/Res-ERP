<?php

namespace App\Filament\Dev\Pages;

use App\Jobs\RebuildInventoryFromSources;
use Throwable;
use App\Jobs\AllocateAllProductsFifoJob;
use Filament\Forms\Components\Select;
use App\Models\Store;
use App\Jobs\ManufacturingBackfillJob;
use App\Jobs\CopyOrderOutToBranchStoreJob;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class DeveloperTools extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationLabel = 'Developer Tools';
    protected static string | \UnitEnum | null $navigationGroup = '⚙️ Developer';
    protected string $view = 'filament.pages.developer-tools';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('Rebuild Inventory')
                ->label('♻️ Rebuild Inventory From Sources')
                ->color('gray')
                ->requiresConfirmation()
                ->action(function () {
                    DB::beginTransaction(); // بدء المعاملة

                    try {
                        // تنفيذ الـ Job مباشرة
                        (new RebuildInventoryFromSources())->handle();
                        DB::commit(); // تأكيد المعاملة
                        showSuccessNotifiMessage('✅ Inventory rebuild job dispatched.');
                    } catch (Throwable $th) {
                        DB::rollBack(); // التراجع عن المعاملة في حال حدوث خطأ
                        showWarningNotifiMessage($th->getMessage());
                    }
                }),

            Action::make('FIFO Allocation')
                ->label('📦 FIFO Allocation for All Products')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function () {
                    DB::beginTransaction(); // بدء المعاملة

                    try {
                        // تنفيذ الـ Job بشكل متزامن
                        (new AllocateAllProductsFifoJob())->handle();
                        DB::commit(); // تأكيد المعاملة

                        showSuccessNotifiMessage('✅ FIFO Allocation command executed successfully.');
                    } catch (Throwable $th) {
                        DB::rollBack(); // التراجع عن المعاملة في حال حدوث خطأ
                        showWarningNotifiMessage("❌ Error: " . $th->getMessage());
                    }
                }),


            Action::make('Copy Order OUT to IN')
                ->label('🔄 Copy Order OUT to IN')
                ->color('success')
                ->requiresConfirmation()
                ->action(function () {

                    try {
                        // تنفيذ الـ Job بشكل متزامن
                        (new CopyOrderOutToBranchStoreJob())->handle();

                        showSuccessNotifiMessage('✅ Order copied from OUT to IN successfully.');
                    } catch (Throwable $th) {
                        showWarningNotifiMessage("❌ Error: " . $th->getMessage());
                    }
                }),


            Action::make('Manufacturing Backfill')
                ->label('⚙️ Manufacturing Backfill (Auto OUT)')
                ->color('danger')
                ->requiresConfirmation()
                ->schema([
                    Select::make('store_id')
                        ->label('Store')
                        ->options(Store::active()->get(['id', 'name'])->pluck('name', 'id'))
                        ->required(),
                ])
                ->action(function (array $data) {
                    $storeId = $data['store_id'];
                    try {
                        // تنفيذ الـ Job بشكل متزامن
                        (new ManufacturingBackfillJob($storeId))->handle();
                        showSuccessNotifiMessage('✅ Manufacturing Backfill command executed successfully.');
                    } catch (Throwable $th) {
                        showWarningNotifiMessage("❌ Error: " . $th->getMessage());
                    }
                }),
            // Action::make('Update Product Unit Prices')
            //     ->label('💰 Update Product Unit Prices')
            //     ->color('success')

            //     ->requiresConfirmation()
            //     ->action(function (array $data) {
            //         $tenantId = $data['tenant_id'] ?? null;
            //         dispatch(new \App\Jobs\UpdateProductUnitPricesJob($tenantId));
            //         showSuccessNotifiMessage('✅ Job dispatched to update product unit prices.');
            //     }),
        ];
    }
    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }
}
