<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource;
use App\Models\Category;
use App\Models\Store;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use App\Jobs\GenerateUnauditedStocktakeJob;
use App\Models\AppLog;
use App\Models\Branch;
use Filament\Schemas\Components\Fieldset;
use Illuminate\Support\Facades\Log;

class ListStockInventories extends ListRecords
{
    protected static string $resource = StockInventoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create_unaudited_stocktake')
                ->label('Create Unaudited Stocktake')
                ->icon('heroicon-o-document-magnifying-glass')
                ->color('warning')
                ->schema([
                    Fieldset::make()->columnSpanFull()
                        ->columns(2)->schema([
                            DatePicker::make('start_date')
                                ->label('Start Date')
                                ->default(Carbon::now())
                                ->required(),
                            DatePicker::make('end_date')
                                ->label('End Date')
                                ->default(Carbon::now())
                                ->required(),
                        ]),
                    Select::make('store_id')
                        ->label('Store')
                        // ->default(8)
                        ->options(fn() => Store::active()
                            ->whereHas('branches', function ($query) {
                                $query->where('type', Branch::TYPE_BRANCH);
                            })
                            ->pluck('name', 'id'))
                        ->required()
                        ->searchable(),

                ])
                ->action(function (array $data) {
                    $tenantId = app(\Spatie\Multitenancy\Contracts\IsTenant::class)::current()?->id;
                    Log::info('StartingJob for tenant: ' . $tenantId);

                    GenerateUnauditedStocktakeJob::dispatch(
                        $data['start_date'],
                        $data['end_date'],
                        true,
                        $data['store_id'],
                        auth()->id(),
                        $tenantId
                    )->onConnection('tenant');

                    Notification::make()
                        ->title('Stocktake Generation Started')
                        ->body('The unaudited stocktake is being generated in the background. You will receive a notification when it is finished.')
                        ->info()
                        ->send();
                })
                ->visible(fn(): bool => isSuperAdmin()),

            CreateAction::make()
                ->icon('heroicon-o-plus-circle')
                ->label('New Stocktake')
                ->visible(fn(): bool => StockInventoryResource::canCreate()),

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
