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

            Action::make('reset_hr_logs')
                ->label('👥 Reset HR Branch Logs')
                ->icon('heroicon-m-arrow-path')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Reset Employee Branch Logs')
                ->modalDescription('This will clear all employee branch logs and create a default one based on current branch and join date for all active tenants and landlord. Are you sure?')
                ->action(function () {
                    try {
                        Artisan::call('hr:reset-branch-logs');
                        showSuccessNotifiMessage('✅ Employee branch logs reset successfully.');
                    } catch (Throwable $th) {
                        showWarningNotifiMessage("❌ Error: " . $th->getMessage());
                    }
                }),
         
        ];
    }
    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }
}
