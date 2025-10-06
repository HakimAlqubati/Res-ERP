<?php

namespace App\Filament\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Throwable;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\BulkAction;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\OrdersExport2;
use App\Filament\Resources\OrderResource\RelationManagers\OrderDetailsRelationManager;
use App\Filament\Resources\OrderResource\RelationManagers\LogsRelationManager;
use App\Filament\Resources\OrderResource\Pages\ListOrders;
use App\Filament\Resources\OrderResource\Pages\CreateOrder;
use App\Filament\Resources\OrderResource\Pages\ViewOrder;
use App\Filament\Resources\OrderResource\Pages\EditOrder;
use App\Filament\Resources\OrderResource\Pages\OrderReportCustom;
use App\Filament\Clusters\MainOrdersCluster;
use App\Filament\Clusters\OrderCluster;
use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Filament\Tables\Columns\SoftDeleteColumn;
use App\Models\Branch;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\UnitPrice;
use App\Models\User;
use Closure;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
// use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderResource extends Resource
{
    protected static ?string $cluster = MainOrdersCluster::class;
    // public static function getPermissionPrefixes(): array
    // {
    //     return [
    //         'view',
    //         'view_any',
    //         'create',
    //         'update',
    //         'delete',
    //         'delete_any',
    //         'publish',
    //     ];
    // }

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 1;
    protected static ?string $model = Order::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::BuildingStorefront;
    // protected static ?string $navigationGroup = 'Orders';
    protected static ?string $recordTitleAttribute = 'id';
    public static function getNavigationLabel(): string
    {
        return __('lang.orders');
    }
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Fieldset::make()->columnSpanFull()->schema([
                    Grid::make()->columnSpanFull()->columns(3)->schema([
                        Select::make('branch_id')->required()
                            ->label(__('lang.branch'))
                            ->options(Branch::where('active', 1)->get(['id', 'name'])->pluck('name', 'id')),
                        Select::make('status')->required()
                            ->label(__('lang.order_status'))
                            ->options(Order::getStatusLabels())->default(Order::ORDERED),
                        DateTimePicker::make('created_at')
                            ->label(__('lang.created_at')),
                        Select::make('stores')->multiple()->required()
                            ->label(__('lang.store'))
                            // ->disabledOn('edit')
                            ->options([
                                Store::active()
                                    // ->withManagedStores()
                                    ->get()->pluck('name', 'id')->toArray()
                            ])->hidden(),
                    ]),
                    // Repeater for Order Details
                    Repeater::make('orderDetails')->columnSpanFull()->hiddenOn(['view', 'edit'])
                        ->label(__('lang.order_details'))->columns(9)
                        ->relationship() // Relationship with the OrderDetails model
                        ->schema([
                            Select::make('product_id')
                                ->label(__('lang.product'))
                                ->searchable()
                                // ->disabledOn('edit')
                                ->options(function () {
                                    return Product::where('active', 1)
                                        ->unmanufacturingCategory()
                                        ->pluck('name', 'id');
                                })
                                ->getSearchResultsUsing(fn(string $search): array => Product::where('active', 1)
                                    ->unmanufacturingCategory()
                                    ->where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id')->toArray())
                                ->getOptionLabelUsing(fn($value): ?string => Product::unmanufacturingCategory()->find($value)?->name)
                                ->reactive()
                                ->afterStateUpdated(fn(callable $set) => $set('unit_id', null))
                                ->searchable()->columnSpan(function ($record) {

                                    if ($record) {
                                        return 2;
                                    } else {
                                        return 3;
                                    }
                                })->required(),
                            Select::make('unit_id')
                                ->label(__('lang.unit'))
                                // ->disabledOn('edit')
                                ->options(
                                    function (callable $get) {

                                        $unitPrices = UnitPrice::where('product_id', $get('product_id'))->get()->toArray();

                                        if ($unitPrices)
                                            return array_column($unitPrices, 'unit_name', 'unit_id');
                                        return [];
                                    }
                                )
                                ->searchable()
                                ->reactive()
                                ->afterStateUpdated(function (Set $set, $state, $get) {
                                    $unitPrice = UnitPrice::where(
                                        'product_id',
                                        $get('product_id')
                                    )->where('unit_id', $state)->first();
                                    $set('price', $unitPrice->price);
                                    $set('total_price', ((float) $unitPrice->price) * ((float) $get('quantity')));
                                    $set('package_size',  $unitPrice->package_size ?? 0);
                                })->columnSpan(2)->required(),
                            TextInput::make('purchase_invoice_id')->label(__('lang.purchase_invoice_id'))->readOnly()->visibleOn('view'),
                            TextInput::make('package_size')->label(__('lang.package_size'))->readOnly()->columnSpan(1),
                            Hidden::make('available_quantity')
                                ->default(1),
                            TextInput::make('quantity')
                                ->label(__('lang.quantity'))
                                ->numeric()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (Set $set, $state, $get) {
                                    $set('available_quantity', $state);

                                    $set('total_price', ((float) $state) * ((float)$get('price') ?? 0));
                                })

                                ->required()->default(1),

                            TextInput::make('price')
                                ->label(__('lang.price'))->readOnly()
                                ->numeric()
                                ->required()->columnSpan(1),
                            TextInput::make('total_price')
                                ->label(__('lang.total_price'))
                                ->numeric()
                                ->readOnly()->columnSpan(1),
                        ])

                        ->createItemButtonLabel(__('lang.add_detail')) // Customize button label
                        ->required(),

                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->paginated([10, 25, 50, 100])
            ->deferLoading()
            ->striped()
            ->extremePaginationLinks()

            ->columns([
                SoftDeleteColumn::make(),
                TextColumn::make('id')->label(__('lang.order_id'))
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->copyable()->alignCenter(true)
                    ->color('primary')
                    ->weight(FontWeight::Bold)
                    ->copyMessage(__('lang.order_id_copied'))
                    ->copyMessageDuration(1500)
                    ->sortable()->searchable()
                    ->searchable(isIndividual: true, isGlobal: false),
                TextColumn::make('customer.name')->label(__('lang.branch_manager'))->toggleable()
                    ->searchable(isIndividual: true)->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip(fn(Model $record): string => "By {$record->customer->name}"),
                TextColumn::make('branch.name')->label(__('lang.branch')),
                // TextColumn::make('store.name')->label(__('lang.store')),
                // TextColumn::make('store_names')->label(__('lang.store'))->toggleable(isToggledHiddenByDefault: true),
                BadgeColumn::make('status')
                    ->label(__('lang.order_status'))
                    ->colors([
                        'primary',
                        'secondary' => static fn($state): bool => $state === Order::PENDING_APPROVAL,
                        'warning' => static fn($state): bool => $state === Order::READY_FOR_DELEVIRY,
                        'success' => static fn($state): bool => $state === Order::DELEVIRED,
                        'danger' => static fn($state): bool => $state === Order::PROCESSING,
                    ])
                    ->iconPosition('after')->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('item_count')->label(__('lang.item_counts'))->alignCenter(true),
                TextColumn::make(
                    'total_amount'
                )->label(__('lang.total_amount'))->alignCenter(true)
                    ->numeric()
                    ->hidden(fn(): bool => isStoreManager())
                    ->formatStateUsing(function ($state) {
                        return formatMoneyWithCurrency($state);
                    })
                    ->summarize(
                        Summarizer::make()
                            ->using(function (Table $table) {
                                $total  = $table->getRecords()->sum(fn($record) => $record->total_amount);
                                if (is_numeric($total)) {
                                    return formatMoneyWithCurrency($total);
                                }
                                return $total;
                            })
                    ),
                TextColumn::make('created_at')
                    ->formatStateUsing(function ($state) {
                        return date('Y-m-d', strtotime($state)) . ' ' . date('H:i:s', strtotime($state));
                    })
                    ->label(__('lang.created_at'))
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->sortable(),


                // TextColumn::make('recorded'),
                // TextColumn::make('orderDetails'),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                // Filter::make('active')
                //     ->query(fn (Builder $query): Builder => $query->where('active', true)),
                SelectFilter::make('status')
                    ->label(__('lang.order_status'))
                    ->multiple()
                    ->searchable()
                    ->options([
                        'ordered' => 'Ordered',
                        'processing' => 'Processing',
                        'ready_for_delivery' => 'Ready for deleviry',
                        'delevired' => 'Delevired',
                        'pending_approval' => 'Pending approval',
                    ]),
                SelectFilter::make('customer_id')
                    ->searchable()
                    ->multiple()
                    ->label(__('lang.branch_manager'))->relationship('customer', 'name'),
                SelectFilter::make('branch_id')
                    ->searchable()
                    ->multiple()
                    ->label(__('lang.branch'))->relationship('branch', 'name'),
                // Filter::make('active')->label(__('lang.active')),
                Filter::make('created_at')
                    ->label(__('lang.created_at'))
                    ->schema([
                        DatePicker::make('created_from')->label(__('lang.from')),
                        DatePicker::make('created_until')->label(__('lang.to')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
                TrashedFilter::make(),

            ], FiltersLayout::Modal)->filtersFormColumns(3)
            ->recordActions([
                Action::make('cancel')
                    ->label('Cancel')->hidden(fn($record): bool => $record->cancelled)
                    ->icon('heroicon-o-backspace')->button()->color(Color::Red)
                    ->schema([
                        Textarea::make('cancel_reason')->required()->label('Cancel Reason')
                    ])
                    ->action(function ($record, $data) {
                        $result = $record->cancelOrder($data['cancel_reason']);

                        if ($result['status'] === 'success') {
                            Notification::make()
                                ->title('Success')
                                ->body($result['message'])
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Error')
                                ->body($result['message'])
                                ->danger()
                                ->send();
                        }
                    })->hidden(fn(): bool => isSuperVisor() || isStoreManager()),
                Action::make('Move')
                    ->button()->requiresConfirmation()
                    ->label(function ($record) {
                        return $record->getNextStatusLabel();
                    })
                    ->icon('heroicon-o-chevron-double-left')

                    ->schema(function () {
                        return [
                            Fieldset::make()->columns(2)->schema([
                                TextInput::make('status')->label('From') // current status

                                    ->default(function ($record) {
                                        return $record->status;
                                    })
                                    ->disabled(),
                                // Input for next status with placeholder showing allowed statuses
                                TextInput::make('next_status')
                                    ->label('To')
                                    ->default(function ($record) {
                                        return implode(', ', array_keys($record->getNextStatuses()));
                                    })->disabled(),

                            ]),

                        ];
                    })

                    ->databaseTransaction()
                    ->action(function ($record) {

                        DB::beginTransaction();
                        try {
                            $currentStatus = $record->STATUS;
                            $nextStatus = implode(', ', array_keys($record->getNextStatuses()));
                            $record->update(['status' => $nextStatus]);
                            showSuccessNotifiMessage('done', "Done Moved to {$nextStatus}");
                            DB::commit();
                        } catch (Throwable $th) {
                            //throw $th;

                            Log::error('error_modify_status', [$th->getMessage()]);
                            showWarningNotifiMessage('Error', $th->getMessage());
                            DB::rollBack();
                        }
                        // Add a log entry for the "moved" action
                    })
                    ->disabled(function ($record) {
                        if ($record->status == Order::DELEVIRED) {
                            return true;
                        }
                        return false;
                    })
                    // ->visible(fn(): bool => isSuperAdmin())
                    ->hidden(),



                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),


                ]),


                // Tables\Actions\RestoreAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
                ForceDeleteBulkAction::make(),
                BulkAction::make('exportOrdersWithDetails')
                    ->label('Export Orders + Details')
                    ->action(function ($records) {
                        return Excel::download(
                            new OrdersExport2($records),
                            'orders_with_details.xlsx'
                        );
                    })
                // ExportBulkAction::make()
            ]);
    }

    public static function getRelations(): array
    {
        return [
            OrderDetailsRelationManager::class,
            LogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {

        return [
            'index' => ListOrders::route('/'),
            'create' => CreateOrder::route('/create'),
            'view' => ViewOrder::route('/{record}'),
            'edit' => EditOrder::route('/{record}/edit'),
            'order-report-custom' => OrderReportCustom::route('/order-report-custom'),

        ];
    }
    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            ListOrders::class,
            // Pages\CreateOrder::class,
            ViewOrder::class,
            EditOrder::class,
        ]);
    }


    protected function getTableReorderColumn(): ?string
    {
        return 'sort';
    }

    protected function getTableRecordActionUsing(): ?Closure
    {
        return null;
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_purchased', 0)
            ->whereHas('orderDetails')
            ->whereHas('branch', function ($query) {
                $query->where('type', '!=', Branch::TYPE_RESELLER); // غيّر "warehouse" لنوع الفرع الذي تريده
            })
            ->forBranchManager()
            ->count();
    }

    public function isTableSearchable(): bool
    {
        return true;
    }

    protected function applySearchToTableQuery(Builder $query): Builder
    {
        if (filled($searchQuery = $this->getTableSearchQuery())) {
            $query->whereIn('id', Order::search($searchQuery)->keys());
        }

        return $query;
    }
    public static function canCreate(): bool
    {
        return false;
        if (isSuperAdmin()) {
            return true;
        }
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return Order::query()
            ->forBranchManager()
            ->where('is_purchased', 0)
            ->whereHas('orderDetails')
            ->whereHas('branch', function ($query) {
                $query->where('type', '!=', Branch::TYPE_RESELLER); // غيّر "warehouse" لنوع الفرع الذي تريده
            })

            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    // public static function getGlobalSearchResultTitle(Model $record): string
    // {
    //     return $record->id;
    // }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return 'Order #' . $record->id;
    }


    public static function canDelete(Model $record): bool
    {
        // return false;
        if (isSuperAdmin()) {
            return true;
        }
        return false;
    }

    public static function canDeleteAny(): bool
    {
        // return false;
        if (isSuperAdmin()) {
            return true;
        }
        return false;
    }


    public static function canEdit(Model $record): bool
    {
        return false;
        if (isSuperAdmin()) {
            return true;
        }
        return false;
    }

    public static function getGlobalSearchResultsLimit(): int
    {
        return 15;
    }
}
