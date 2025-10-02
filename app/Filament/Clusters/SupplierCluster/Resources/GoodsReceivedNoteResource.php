<?php

namespace App\Filament\Clusters\SupplierCluster\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Actions\EditAction;
use Filament\Actions\ActionGroup;
use Filament\Actions\Action;
use Throwable;
use App\Models\InventoryTransaction;
use Exception;
use Filament\Actions\BulkActionGroup;
use App\Filament\Clusters\SupplierCluster\Resources\GoodsReceivedNoteResource\Pages\ListGoodsReceivedNotes;
use App\Filament\Clusters\SupplierCluster\Resources\GoodsReceivedNoteResource\Pages\CreateGoodsReceivedNote;
use App\Filament\Clusters\SupplierCluster\Resources\GoodsReceivedNoteResource\Pages\EditGoodsReceivedNote;
use App\Filament\Clusters\SupplierCluster\Resources\GoodsReceivedNoteResource\Pages\EditGoodsReceivedNoteV3;
use App\Filament\Clusters\SupplierCluster;
use App\Filament\Clusters\SupplierCluster\Resources\GoodsReceivedNoteResource\Pages;
use App\Filament\Clusters\SupplierCluster\Resources\GoodsReceivedNoteResource\RelationManagers;
use App\Filament\Clusters\SupplierCluster\Resources\GoodsReceivedNoteResource\RelationManagers\GrnDetailsRelationManager;
use App\Models\GoodsReceivedNote;
use App\Models\Product;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\UnitPrice;

use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Pages\Page;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Unique;

class GoodsReceivedNoteResource extends Resource
{
    protected static ?string $model = GoodsReceivedNote::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::ClipboardDocumentCheck;
    protected static ?string $cluster = SupplierCluster::class;
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return 'GRN';
    }

    public static function getModelLabel(): string
    {
        return 'GRN';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return settingWithDefault('purchase_invoice_from_grn_only', false);
    }


    public static function form(Schema $schema): Schema
    {
        $isEditOperation = $schema->getOperation() == 'edit';

        return $schema
            ->components([
                Section::make([
                    Grid::make(3)
                        ->schema([
                            TextInput::make('grn_number')
                                ->label('GRN Number')
                                ->default(fn(): int => (GoodsReceivedNote::query()
                                    ->orderBy('id', 'desc')
                                    ->value('id') + 1 ?? 1))
                                ->unique(ignoreRecord: true)
                                ->readOnly()->disabled(fn($record): bool => $isEditOperation && $record->status == GoodsReceivedNote::STATUS_APPROVED ? true : false)
                                ->helperText('Enter GRN Number')->required(),
                            DatePicker::make('grn_date')
                                ->label('GRN Date')->default(now())
                                ->required()->disabled(fn($record): bool => $isEditOperation && $record->status == GoodsReceivedNote::STATUS_APPROVED ? true : false),


                            Select::make('store_id')
                                ->options(
                                    Store::active()->pluck('name', 'id')->toArray()
                                )->default(getDefaultStore())
                                ->label('Store')->searchable()
                                ->required()->disabled(fn($record): bool => $isEditOperation && $record->status == GoodsReceivedNote::STATUS_APPROVED ? true : false),
                            Select::make('status')->disabled()->dehydrated()
                                ->label('Status')->default(GoodsReceivedNote::STATUS_CREATED)
                                ->options(GoodsReceivedNote::getStatusOptions())
                                ->required()
                                ->disabled(fn($record): bool => $isEditOperation && $record->status == GoodsReceivedNote::STATUS_APPROVED ? true : false),
                            Select::make('supplier_id')->label(__('lang.supplier'))
                                ->getSearchResultsUsing(fn(string $search): array => Supplier::where('name', 'like', "%{$search}%")->limit(10)->pluck('name', 'id')->toArray())
                                ->getOptionLabelUsing(fn($value): ?string => Supplier::find($value)?->name)
                                ->searchable()
                                ->options(Supplier::limit(5)->get(['id', 'name'])->pluck('name', 'id'))
                                ->disabled(fn($record): bool => $isEditOperation && $record->status == GoodsReceivedNote::STATUS_APPROVED ? true : false),
                            Textarea::make('notes')
                                ->label('Notes')
                                ->columnSpanFull()->disabled(fn($record): bool => $isEditOperation && $record->status == GoodsReceivedNote::STATUS_APPROVED ? true : false),
                        ]),


                    Fieldset::make('Details')->columnSpanFull()
                        ->schema([
                            Repeater::make('grnDetails')->columnSpanFull()
                                ->relationship()
                                ->label('Items')
                                ->columns(6)
                                ->schema([
                                    Select::make('product_id')
                                        ->label(__('lang.product'))
                                        ->distinct()
                                        ->searchable()
                                        ->options(function () {
                                            return Product::active()
                                                ->unmanufacturingCategory()
                                                ->orderBy('id', 'asc')
                                                ->limit(10)
                                                ->get()
                                                ->mapWithKeys(fn($product) => [
                                                    $product->id => "{$product->code} - {$product->name}"
                                                ]);
                                        })
                                        ->getSearchResultsUsing(function (string $search): array {
                                            return Product::where('active', 1)
                                                ->where(function ($query) use ($search) {
                                                    $query->where('name', 'like', "%{$search}%")
                                                        ->orWhere('code', 'like', "%{$search}%");
                                                })->unmanufacturingCategory()
                                                ->limit(50)
                                                ->get()
                                                ->mapWithKeys(fn($product) => [
                                                    $product->id => "{$product->code} - {$product->name}"
                                                ])
                                                ->toArray();
                                        })
                                        ->getOptionLabelUsing(fn($value): ?string => Product::unmanufacturingCategory()->find($value)?->code . ' - ' . Product::find($value)?->name)
                                        ->reactive()
                                        ->afterStateUpdated(function ($set, $state) {
                                            $set('unit_id', null);
                                            $product = Product::find($state);
                                            $set('waste_stock_percentage', $product?->waste_stock_percentage);
                                        })
                                        ->searchable()->columnSpan(2)
                                        ->required(),
                                    Select::make('unit_id')
                                        ->label(__('lang.unit'))
                                        ->options(function (callable $get) {
                                            $product = Product::find($get('product_id'));
                                            if (!$product)
                                                return [];

                                            return $product->supplyUnitPrices
                                                ->pluck('unit.name', 'unit_id')?->toArray() ?? [];
                                        })
                                        // ->searchable()
                                        ->reactive()
                                        ->afterStateUpdated(function (Set $set, $state, $get) {
                                            $unitPrice = UnitPrice::where(
                                                'product_id',
                                                $get('product_id')
                                            )
                                                ->where('unit_id', $state)->first();
                                            $set('package_size',  $unitPrice->package_size ?? 0);
                                        })->columnSpan(2)->required(),
                                    TextInput::make('package_size')->type('number')->readOnly()->columnSpan(1)
                                        ->label(__('lang.package_size')),
                                    TextInput::make('quantity')
                                        ->label(__('lang.quantity'))

                                        ->numeric()

                                        ->minValue(0.1)
                                        ->default(1)

                                        ->live(onBlur: true)
                                        ->columnSpan(1)->required()
                                        ->formatStateUsing(fn($state) => round((float) $state, 2)),


                                ])
                                ->createItemButtonLabel('Add Item')
                                ->collapsible()
                                ->disabled(fn($record): bool => $isEditOperation && $record->status == GoodsReceivedNote::STATUS_APPROVED ? true : false)
                                ->addable(function ($record) use ($isEditOperation) {

                                    if ($isEditOperation && $record->status == GoodsReceivedNote::STATUS_APPROVED) {
                                        return false;
                                    }
                                    return true;
                                })
                                ->defaultItems(1),
                        ]),


                ])->columnSpanFull()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->striped()
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->sortable()->alignCenter(true)
                    ->label('ID')->toggleable()
                    ->color('primary')
                    ->weight(FontWeight::Bold),
                TextColumn::make('grn_number')
                    ->sortable()
                    ->label('GRN Number')
                    ->color('primary')
                    ->weight(FontWeight::Bold)
                    ->searchable()->alignCenter(true)->toggleable(),
                TextColumn::make('grn_date')->label('Date')->date()->toggleable(),
                TextColumn::make('store.name')->label('Store')->searchable()->toggleable(),
                TextColumn::make('supplier.name')->label('Supplier')->searchable()->toggleable(),
                // TextColumn::make('status')->label('Status')->badge()->toggleable(),
                TextColumn::make('details_count')->alignCenter(true)
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('updated_at')->alignCenter(true)
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->sortable(),
                TextColumn::make('approve_date')->alignCenter(true)
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->sortable(),
                IconColumn::make('has_inventory_transaction')
                    ->label('Inventory Updated')
                    ->boolean()->toggleable(isToggledHiddenByDefault: true)
                    ->alignCenter(),
                IconColumn::make('belongs_to_purchase_invoice')
                    ->label('Belongs to Invoice')
                    ->boolean()->toggleable(isToggledHiddenByDefault: true)
                    ->alignCenter(),
                IconColumn::make('has_outbound_transactions')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Untouched')->boolean()->alignCenter(),
                IconColumn::make('cancelled')
                    ->label('Cancelled')->toggleable(isToggledHiddenByDefault: true)->boolean()->alignCenter(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(), // Add soft delete filter
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn($record): bool => $record->status == GoodsReceivedNote::STATUS_CREATED),
                // Tables\Actions\Action::make('Reject')
                //     ->label('Reject')
                //     ->color('danger')->button()
                //     ->icon('heroicon-o-x-circle')
                //     ->form([
                //         Textarea::make('cancel_reason')
                //             ->label('Rejection Reason')
                //             ->required(),
                //     ])
                //     ->action(function ($record, array $data) {
                //         $record->update([
                //             'status' => GoodsReceivedNote::STATUS_REJECTED,
                //             'cancel_reason' => $data['cancel_reason'],
                //         ]);
                //     })
                //     ->requiresConfirmation(),

                // Tables\Actions\Action::make('Cancel')
                //     ->label('Cancel')
                //     ->color('warning')->button()
                //     ->icon('heroicon-o-backspace')
                //     ->form([
                //         Textarea::make('cancel_reason')
                //             ->label('Cancellation Reason')
                //             ->required(),
                //     ])
                //     ->action(function ($record, array $data) {
                //         $record->update([
                //             'status' => GoodsReceivedNote::STATUS_CANCELLED,
                //             'cancel_reason' => $data['cancel_reason'],
                //         ]);
                //     })
                //     ->requiresConfirmation(),
                ActionGroup::make([
                    Action::make('cancel')
                        ->label('Cancel')->button()
                        ->color('danger')
                        ->icon('heroicon-o-x-circle')
                        ->requiresConfirmation()
                        ->schema([
                            Textarea::make('reason')
                                ->label('Cancellation Reason')
                                ->required()
                                ->rows(3)->columnSpanFull(),
                        ])
                        ->action(function (array $data, GoodsReceivedNote $record) {
                            try {
                                DB::beginTransaction();

                                $result = $record->handleCancellation($record, $data['reason']);

                                DB::commit();

                                if (! $result['status']) {
                                    showWarningNotifiMessage('Failed', $result['message']);
                                    DB::rollBack();
                                }
                                if ($result['status'])
                                    showSuccessNotifiMessage('Success', $result['message']);
                            } catch (Throwable $e) {
                                DB::rollBack();
                                showWarningNotifiMessage('Error', $e->getMessage());
                                DB::rollBack();
                                report($e);
                            }
                        })
                        ->visible(fn(GoodsReceivedNote $record) => ! $record->cancelled),
                    Action::make('create_inventory')
                        ->label('Create Inventory')
                        ->icon('heroicon-o-plus-circle')->button()
                        ->color('success')
                        ->visible(fn($record) => !$record->has_inventory_transaction)
                        ->action(function ($record) {
                            DB::beginTransaction();
                            try {
                                $notes = 'GRN with id ' . $record->id;
                                if ($record->store?->name) {
                                    $notes .= ' in (' . $record->store->name . ')';
                                }
                                foreach ($record->grnDetails as $detail) {
                                    InventoryTransaction::moveToStore([
                                        'product_id' => $detail->product_id,
                                        'movement_type' => InventoryTransaction::MOVEMENT_IN,
                                        'quantity' => $detail->quantity,
                                        'unit_id' => $detail->unit_id,
                                        'package_size' => $detail->package_size,
                                        'store_id' => $record->store_id,
                                        'price' => getUnitPrice($detail->product_id, $detail->unit_id),
                                        'transaction_date' => $record->date,
                                        'movement_date' => $record->date,
                                        'notes' => $notes,
                                        'transactionable' => $record,
                                    ]);
                                }
                                DB::commit();
                                showSuccessNotifiMessage('Done');
                            } catch (Exception $e) {
                                DB::rollBack();
                                showWarningNotifiMessage($e->getMessage());
                            }
                        })->hidden()
                ]),
                Action::make('Approve')
                    ->label(fn($record): string =>  $record->status == GoodsReceivedNote::STATUS_APPROVED ? 'Approved' : 'Approve')
                    ->disabled(fn($record): bool =>  $record->status == GoodsReceivedNote::STATUS_APPROVED ? true : false)
                    ->color('success')->button()
                    ->icon('heroicon-o-check-badge')
                    ->requiresConfirmation()
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => GoodsReceivedNote::STATUS_APPROVED,
                            'approved_by' => auth()->id(),
                            'approve_date' => now(),
                        ]);
                    })
                    ->requiresConfirmation(),
                Action::make('CreatePurchaseInvoice')
                    ->label('Input Prices')
                    ->color('primary')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->url(fn($record) => static::getUrl('create-purchase-invoice', ['record' => $record]))
                    ->button()
                    ->visible(function ($record) {
                        $allowedRoles = setting('grn_approver_role_id', []);
                        $userRoles = auth()->user()?->roles->pluck('id')->toArray() ?? [];

                        return $record->status == GoodsReceivedNote::STATUS_APPROVED && !$record->is_purchase_invoice_created  &&
                            (count(array_intersect($userRoles, $allowedRoles)) > 0);
                    }),

            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // GrnDetailsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGoodsReceivedNotes::route('/'),
            'create' => CreateGoodsReceivedNote::route('/create'),
            'edit' => EditGoodsReceivedNote::route('/{record}/edit'),
            // 'create-purchase-invoice' => Pages\EditGoodsReceivedNoteV2::route('/{record}/create-purchase-invoice'),
            'create-purchase-invoice' => EditGoodsReceivedNoteV3::route('/{record}/create-purchase-invoice'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            ListGoodsReceivedNotes::class,
            CreateGoodsReceivedNote::class,
            EditGoodsReceivedNote::class,
        ]);
    }

    public static function canCreate(): bool
    {
        $allowedRoles = setting('grn_entry_role_id', []);
        $userRoles = auth()->user()?->roles->pluck('id')->toArray() ?? [];

        return count(array_intersect($userRoles, $allowedRoles)) > 0;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
        if (isSystemManager() || isBranchManager() || isSuperAdmin()) {
            return true;
        }
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
        if (isSystemManager() || isBranchManager() || isSuperAdmin()) {
            return true;
        }
        return false;
    }
}
