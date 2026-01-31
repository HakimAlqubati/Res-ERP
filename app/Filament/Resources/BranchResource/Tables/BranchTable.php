<?php

namespace App\Filament\Resources\BranchResource\Tables;

use App\Models\Branch;
use App\Models\Store;
use App\Models\User;
use App\Services\Financial\TransferFinancialSyncService;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Throwable;
use App\Jobs\ZeroStoreStockJob;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Table;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Model;

class BranchTable
{
    public static function configure(Table $table): Table
    {
        return $table->striped()
            ->columns([
                TextColumn::make('id')->label(__('lang.branch_id'))->alignCenter(true)->toggleable(isToggledHiddenByDefault: true),
                SpatieMediaLibraryImageColumn::make('default')->label('')->size(50)
                    ->circular()->alignCenter(true)->getStateUsing(function () {
                        return null;
                    })->limit(3),
                TextColumn::make('name')->label(__('lang.name'))->searchable(),
                TextColumn::make('type_title')->label(__('lang.branch_type')),
                IconColumn::make('active')->boolean()->label(__('lang.active'))->alignCenter(true),
                TextColumn::make('address')->label(__('lang.address'))
                    // ->limit(100)
                    ->words(5)->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('user.name')->label(__('lang.branch_manager'))->toggleable(),
                TextColumn::make('category_names')->label(__('stock.customized_manufacturing_categories'))->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('user.email')->label('Email')->copyable()->toggleable(),

                TextColumn::make('start_date')
                    ->label(__('lang.start_date'))
                    ->dateTime('Y-m-d')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('end_date')
                    ->label(__('lang.end_date'))
                    ->dateTime('Y-m-d')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('orders_count')
                    ->formatStateUsing(fn($record): string => $record?->orders()?->count() ?? 0)
                    ->label(__('lang.orders'))->alignCenter(true)->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('store.name')

                    ->label(__('lang.store'))->alignCenter(true)->toggleable(isToggledHiddenByDefault: false),

            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('active')
                    ->options([
                        1 => __('lang.active'),
                        0 => __('lang.status_unactive'),
                    ])->default(1),

            ])
            ->recordActions([


                ActionGroup::make([
                    Action::make('sync_transfers')
                        ->label('Sync Financial Transfers')
                        ->icon('heroicon-o-arrow-path')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Sync Transfer Orders to Financial Transactions')
                        ->modalDescription(fn($record) => "This will sync all transfer orders for branch: {$record->name}")
                        ->modalIcon('heroicon-o-banknotes')
                        ->action(function (Model $record) {
                            try {
                                $syncService = new TransferFinancialSyncService(new \App\Services\Orders\OrderCostAnalysisService());

                                // Sync transfers for this specific branch
                                $result = $syncService->syncTransfersForBranch($record->id);

                                if (!$result['success']) {
                                    Notification::make()
                                        ->title('Sync Failed')
                                        ->body($result['message'])
                                        ->danger()
                                        ->send();
                                    return;
                                }

                                // Build success message
                                $message = "✅ {$result['message']}\n\n";
                                $message .= "📊 Summary:\n";
                                $message .= "• Total Orders: {$result['total_orders']}\n";
                                $message .= "• Synced: {$result['synced']}\n";
                                $message .= "• Skipped: {$result['skipped']}\n";
                                $message .= "• Errors: {$result['errors']}";

                                if ($result['errors'] > 0) {
                                    Notification::make()
                                        ->title('Sync Completed with Errors')
                                        ->body($message)
                                        ->warning()
                                        ->duration(10000)
                                        ->send();
                                } else {
                                    Notification::make()
                                        ->title('Sync Completed Successfully')
                                        ->body($message)
                                        ->success()
                                        ->duration(8000)
                                        ->send();
                                }
                            } catch (Throwable $e) {
                                Notification::make()
                                    ->title('Error')
                                    ->body('Failed to sync transfers: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                ]),

                Action::make('addStore')
                    ->label('Add Store')
                    ->icon('heroicon-o-plus-circle')
                    ->visible(fn(Model $record) => ! $record->store && $record->type != Branch::TYPE_HQ)
                    ->schema([
                        TextInput::make('name')
                            ->label('Store Name')
                            ->default(fn(Model $record) => $record->name . ' Store')
                            ->required(),

                        Toggle::make('active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->action(function (Model $record, array $data) {
                        try {
                            //code...
                            $store = Store::create([
                                'name'      => $data['name'],
                                'active'    => $data['active'],
                                'branch_id' => $record->id,
                            ]);
                            $record->update(['store_id' => $store->id]);
                        } catch (Throwable $th) {
                            throw $th;
                        }
                    })
                    ->modalHeading('Create and Link Store')
                    ->color('primary')
                    ->button(),
                Action::make('add_area')
                    ->modalHeading('')
                    ->modalWidth('lg') // Adjust modal size
                    ->button()
                    ->icon('heroicon-o-plus')
                    ->label('Add area')->schema([
                        Repeater::make('branch_areas')
                            ->minItems(1)
                            ->maxItems(1)
                            ->disableItemCreation(true)
                            ->disableItemDeletion(true)

                            ->schema([
                                TextInput::make('name')->label('Area name')->required()->helperText('Type the name of area'),
                                Textarea::make('description')->label('Description')->helperText('More information about the area, like floor, location ...etc'),
                            ])
                            ->afterStateUpdated(function ($state, $record) {

                                // Custom logic to handle saving without deleting existing records
                                $branch = $record; // Get the branch being updated
                                $existingAreas = $branch->areas->pluck('id')->toArray(); // Existing area IDs

                                foreach ($state as $areaData) {
                                    if (!isset($areaData['id'])) {
                                        // If it's a new area, create it
                                        $branch->areas()->create($areaData);
                                    } else {
                                    }
                                }
                            }),
                    ]),
                Action::make('quick_edit')
                    ->label(__('Quick Edit'))
                    ->icon('heroicon-o-pencil-square')
                    ->modalHeading(__('Quick Edit Branch'))
                    ->modalWidth('lg')
                    ->schema(function ($record) {
                        return [
                            TextInput::make('name')->required()->label(__('lang.name'))->default($record->name),
                            Select::make('manager_id')
                                ->label(__('lang.branch_manager'))->default($record->manager_id)
                                ->options(User::whereHas('roles', fn($q) => $q->where('id', 7))
                                    ->pluck('name', 'id')),
                            Select::make('store_id')
                                ->label(__('stock.store_id'))->default($record->store_id)
                                ->options(Store::active()->centralKitchen()->pluck('name', 'id'))
                                ->searchable()
                                ->requiredIf('type', Branch::TYPE_CENTRAL_KITCHEN),

                        ];
                    })
                    ->action(function (Model $record, array $data) {
                        $record->update($data);
                        Notification::make()
                            ->title(__('Updated successfully'))
                            ->success()
                            ->send();
                    }),
                Action::make('zero_stock')
                    ->label(__('stock.zero_stock'))
                    ->icon('heroicon-o-archive-box-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading(__('stock.zero_stock_heading'))
                    ->modalDescription(fn($record) => __('stock.zero_stock_confirmation', ['branch' => $record->name]))
                    ->visible(fn(Model $record) => (bool)$record->store_id && isSuperAdmin())
                    ->action(function (Model $record) {
                        try {
                            \Illuminate\Support\Facades\DB::table('inventory_transactions')
                                ->where('store_id', $record->store_id)
                                // ->where('movement_type','in')
                                ->whereNull('deleted_at')
                                ->update(['deleted_at' => now()]);

                            Notification::make()
                                ->title(__('stock.zero_stock_success', ['count' => '...']))
                                ->success()
                                ->send();
                        } catch (Throwable $e) {
                            Notification::make()
                                ->title(__('stock.zero_stock_failed'))
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
                ForceDeleteBulkAction::make(),
                RestoreBulkAction::make(),
            ]);
    }
}
