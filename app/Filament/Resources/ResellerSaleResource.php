<?php

namespace App\Filament\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Exception;
use App\Models\ResellerSalePaidAmount;
use Filament\Notifications\Notification;
use Throwable;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\ResellerSaleResource\Pages\ListResellerSales;
use App\Filament\Resources\ResellerSaleResource\Pages\CreateResellerSale;
use App\Filament\Resources\ResellerSaleResource\Pages\EditResellerSale;
use App\Filament\Resources\ResellerSaleResource\Pages\PrintResellerInvoice;
use App\Filament\Clusters\ResellersCluster;
use App\Filament\Resources\ResellerSaleResource\Pages;
use App\Models\Branch;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\ResellerSale;
use App\Models\UnitPrice;
use App\Services\MultiProductsInventoryService;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;

class ResellerSaleResource extends Resource
{
    protected static ?string $model = ResellerSale::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster                             = ResellersCluster::class;
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort                         = 2;

    public static function getLabel(): ?string
    {
        return __('lang.reseller_sale');
    }

    public static function getPluralLabel(): ?string
    {
        return __('lang.reseller_sales');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Fieldset::make(__('lang.reseller_sale_info'))->columnSpanFull()->schema([
                    Grid::make(2)->columnSpanFull()->schema([
                        Select::make('branch_id')
                            ->label(__('lang.reseller'))
                            ->options(
                                Branch::resellers()->active()->pluck('name', 'id')
                            )
                            ->disabledOn('edit')
                            ->required()->preload()
                            ->searchable(),

                        DatePicker::make('sale_date')
                            ->label(__('lang.sale_date'))
                            ->disabledOn('edit')
                            ->default(now())
                            ->required(),
                    ]),

                    Textarea::make('note')
                        ->label(__('lang.note'))
                        ->columnSpanFull(),
                ]),

                Repeater::make('items')
                    ->label(__('lang.items'))
                    ->relationship()
                    ->defaultItems(1)->columnSpanFull()
                    ->columns(15)
                    // ->table([
                    //     TableColumn::make(__('lang.product'))
                    //         ->width('3fr'),
                    //     TableColumn::make(__('lang.unit'))->width('50'),
                    //     TableColumn::make('P.Size'),
                    //     TableColumn::make(__('stock.qty_in_stock')),
                    //     TableColumn::make(__('lang.quantity')),
                    //     TableColumn::make(__('lang.unit_price')),
                    //     TableColumn::make(__('lang.total_price')),
                    // ])
                    ->disabledOn('edit')
                    ->columnSpanFull()
                    ->schema([
                        Select::make('product_id')
                            ->label(__('lang.product'))
                            ->afterStateUpdated(function ($set, $state, $get) {
                                $set('unit_id', null); // reset old

                                if (! $state) {
                                    return;
                                }

                                $product = Product::find($state);
                                if (! $product) {
                                    return;
                                }

                                $unitPrices = $product->unitPrices->pluck('unit.name', 'unit_id');
                                if ($unitPrices->isNotEmpty()) {
                                    $firstUnitId = $unitPrices->keys()->first();
                                    $set('unit_id', $firstUnitId);

                                    $unitPrice        = $product->unitPrices->firstWhere('unit_id', $firstUnitId);
                                    $unitSellingPrice = round($unitPrice?->selling_price ?? 0, 2);
                                    $set('unit_price', $unitSellingPrice);
                                    $set('package_size', $unitPrice?->package_size ?? 0);

                                    $quantity = (float) $get('quantity') ?: 1;
                                    $set('total_price', round($unitSellingPrice * $quantity, 2));
                                }
                            })
                            ->options(function (callable $get) {
                                $storeId = Branch::find($get('../../branch_id'))?->store_id;

                                if (! $storeId) {
                                    return [];
                                }

                                return Product::whereHas('inventoryTransactions', function ($q) use ($storeId) {
                                    $q->where('store_id', $storeId);
                                })
                                    ->get()
                                    ->pluck('name', 'id');
                            })->preload()
                            ->reactive()

                            ->searchable()
                            ->required()->columnSpan(4),

                        Select::make('unit_id')
                            ->label(__('lang.unit'))
                            ->options(function (callable $get) {
                                $product = Product::find($get('product_id'));
                                if (! $product) {
                                    return [];
                                }

                                return $product->unitPrices->pluck('unit.name', 'unit_id')->toArray();
                            })
                            ->searchable()
                            ->reactive()
                            ->afterStateUpdated(function (Set $set, $state, $get) {

                                $productId = $get('product_id');
                                $storeId = Branch::find($get('../../branch_id'))?->store_id;
                                $unitId = $state;

                                if (!$unitId || !$productId || !$storeId) {
                                    $set('qty_in_stock', 0);
                                    return;
                                }

                                $service = new MultiProductsInventoryService(
                                    null,
                                    $productId,
                                    $unitId,
                                    $storeId
                                );

                                $remainingQty = $service->getInventoryForProduct($productId)[0]['remaining_qty'] ?? 0;
                                $set('qty_in_stock', $remainingQty);

                                $unitPrice = UnitPrice::where(
                                    'product_id',
                                    $get('product_id')
                                )
                                    // ->supplyOutUnitPrices()
                                    ->where('unit_id', $state)->first();
                                $unitSellingPrice = round($unitPrice?->selling_price, 2) ?? 0;
                                $set('unit_price', $unitSellingPrice ?? 0);
                                $total = round(((float) ($unitSellingPrice ?? 0)) * ((float) $get('quantity')), 2) ?? 0;

                                $set('total_price', $total ?? 0);
                                $set('package_size', $unitPrice->package_size ?? 0);
                            })
                            ->searchable()->placeholder('Select')
                            ->required()->columnSpan(2),

                        TextInput::make('package_size')
                            // ->label(__('lang.package_size'))
                            ->label('P.Size')
                            ->numeric()->type('number')->readOnly()
                            ->required()->columnSpan(1),

                        TextInput::make('qty_in_stock')
                            ->default(0)->columnSpan(2)
                            ->label(__('stock.qty_in_stock'))->disabled(),

                        TextInput::make('quantity')
                            ->label(__('lang.quantity'))
                            ->disabledOn('edit')
                            ->default(1)
                            ->numeric()
                            ->minValue(0.1)
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($set, $state,   $get) {
                                $qty = (float) ($state ?? 0);

                                // فضّل قيمة unit_price المدخلة يدويًا أولًا
                                $unitPrice = (float) ($get('unit_price') ?? 0);

                                // إن لم تكن موجودة استخدم السعر الافتراضي من UnitPrice
                                if ($unitPrice <= 0) {
                                    $unitPrice = (float) (UnitPrice::query()
                                        ->where('product_id', $get('product_id'))
                                        ->where('unit_id', $get('unit_id'))
                                        ->value('selling_price') ?? 0);

                                    // اختياري: تعبئة الحقل للمستخدم
                                    if ($unitPrice > 0) {
                                        $set('unit_price', $unitPrice);
                                    }
                                }

                                $set('total_price', round($qty * $unitPrice, 2));
                            })->columnSpan(2),

                        TextInput::make('unit_price')
                            ->label(__('lang.unit_price'))
                            ->numeric()
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($set, $state,   $get) {
                                $qty       = (float) ($get('quantity') ?? 0);
                                $unitPrice = (float) ($state ?? 0);
                                $set('total_price', round($qty * $unitPrice, 2));
                            })->columnSpan(2),

                        TextInput::make('total_price')
                            ->label(__('lang.total_price'))
                            ->numeric()
                            ->dehydrated()
                            ->readOnly()->disabled()->columnSpan(2),

                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->striped()->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')->sortable()->searchable()->toggleable()->alignCenter(),
                TextColumn::make('branch.name')->label(__('lang.reseller'))->toggleable(),
                TextColumn::make('store.name')->label(__('lang.store'))->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('sale_date')->date('Y-m-d')->toggleable(),
                TextColumn::make('item_count')
                    ->label(__('lang.item_counts'))
                    ->alignCenter()
                    ->toggleable(),

                TextColumn::make('total_amount')
                    ->label(__('lang.total_amount'))
                    ->formatStateUsing(fn($state) => formatMoneyWithCurrency($state)),
                TextColumn::make('is_cancelled')
                    ->label(__('lang.is_cancelled'))
                    ->formatStateUsing(fn($state) => $state ? '❌ ' . __('lang.cancelled') : '✅ Active')
                    ->badge()
                    ->color(fn($state) => $state ? 'danger' : 'success')
                    ->alignCenter()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('total_paid')
                    ->label(__('lang.paid'))
                    ->formatStateUsing(fn($state) => formatMoneyWithCurrency($state))
                    ->toggleable(),

                TextColumn::make('remaining_amount')
                    ->label(__('lang.remaining'))
                    ->formatStateUsing(fn($state) => formatMoneyWithCurrency($state))
                    ->toggleable(),
            ])
            ->deferFilters(false)
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('branch_id')
                    ->label(__('lang.reseller'))->multiple()
                    ->options(
                        Branch::resellers()->active()->pluck('name', 'id')
                    )
                    ->preload()
                    ->searchable(),
                Filter::make('date_range')
                    ->schema([
                        DatePicker::make('from')->label('From Date'),
                        DatePicker::make('to')->label('To Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn($q, $date) => $q->whereDate('sale_date', '>=', $date))
                            ->when($data['to'], fn($q, $date) => $q->whereDate('sale_date', '<=', $date));
                    })
                    ->label('Date Between')
                    ->indicateUsing(function (array $data): ?string {
                        if ($data['from'] && $data['to']) {
                            return "From {$data['from']} to {$data['to']}";
                        }
                        if ($data['from']) {
                            return "From {$data['from']}";
                        }
                        if ($data['to']) {
                            return "Until {$data['to']}";
                        }
                        return null;
                    }),

            ], FiltersLayout::Modal)->filtersFormColumns(3)
            ->recordActions([

                // ✅ زر الإلغاء الجديد
                Action::make('cancel')->button()
                    ->label(__('lang.cancel'))
                    ->icon('heroicon-o-backspace')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->schema([
                        Textarea::make('cancel_reason')->columnSpanFull()
                            ->label(__('lang.cancel_reason'))
                            ->required(),
                    ])
                    ->hidden(fn(ResellerSale $record): bool => (bool) $record->is_cancelled) // يخفيه لو كان ملغي
                    ->action(function (array $data, ResellerSale $record) {
                        try {
                            DB::transaction(function () use ($data, $record) {
                                InventoryTransaction::where('transactionable_id', $record->id)
                                    ->where('transactionable_type', ResellerSale::class)
                                    ->delete();

                                $record->is_cancelled = true;
                                $record->cancel_reason =  $data['cancel_reason'];
                                $record->save();
                            });

                            Notification::make()
                                ->title(__('done'))
                                ->success()
                                ->send();
                        } catch (Throwable $e) {
                            report($e);

                            Notification::make()
                                ->title(__('lang.error'))
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('print_invoice')
                    ->label('Print')
                    ->hidden(fn($record) => $record->is_cancelled)
                    ->icon('heroicon-o-printer')
                    ->url(
                        fn(ResellerSale $record) =>
                        \App\Filament\Resources\ResellerSaleResource::getUrl(name: 'print', parameters: ['record' => $record])
                    )->button()
                    ->openUrlInNewTab(),

                ViewAction::make(),
                Action::make('add_payment')
                    ->label(__('lang.add_payment')) // استخدم مفتاح ترجمة إن وجد
                    ->icon('heroicon-o-banknotes')
                    ->button()
                    ->schema(function (ResellerSale $record) {
                        $remaining = $record->remaining_amount;

                        $remaining = round($remaining, 2);
                        return [
                            Fieldset::make()->columns(2)->schema([
                                TextInput::make('amount')
                                    ->label(__('lang.amount'))
                                    ->numeric()
                                    ->minValue(0.01)
                                    ->maxValue($remaining)
                                    ->default($remaining)
                                    ->required()
                                    ->helperText("Remaining: " . formatMoneyWithCurrency($remaining)),

                                DatePicker::make('paid_at')
                                    ->label(__('lang.paid_at'))
                                    ->default(now())
                                    ->required(),

                                Textarea::make('notes')
                                    ->label(__('lang.notes'))
                                    ->rows(2)
                                    ->maxLength(500)
                                    ->columnSpanFull(),
                            ]),
                        ];
                    })
                    ->action(function (array $data, ResellerSale $record): void {
                        try {
                            $remaining = round($record->remaining_amount, 2);

                            $amount = round($data['amount'], 2);
                            if ($amount > $remaining || $amount <= 0) {
                                throw new Exception("Invalid payment amount. Remaining is: {$remaining}");
                            }

                            DB::transaction(function () use ($data, $record, $amount) {
                                ResellerSalePaidAmount::create([
                                    'reseller_sale_id' => $record->id,
                                    'amount'           => $amount,
                                    'paid_at'          => $data['paid_at'],
                                    'notes'            => $data['notes'] ?? null,
                                    'created_by'       => auth()->id(),
                                ]);
                            });

                            Notification::make()
                                ->title(__('lang.payment_added_successfully'))
                                ->success()
                                ->send();
                        } catch (Throwable $e) {
                            report($e);

                            Notification::make()
                                ->title(__('lang.error_while_adding_payment'))
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })->hidden(fn($record) => $record->is_cancelled),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListResellerSales::route('/'),
            'create' => CreateResellerSale::route('/create'),
            // 'edit'   => EditResellerSale::route('/{record}/edit'),
            'view'   => EditResellerSale::route('/{record}'),
            'print'  => PrintResellerInvoice::route('/{record}/print'),
        ];
    }
    public static function getNavigationBadge(): ?string
    {
        return self::getEloquentQuery()->count();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);
    }
    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            ListResellerSales::class,
            CreateResellerSale::class,
            EditResellerSale::class,
        ]);
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
    public static function canDelete(Model $record): bool
    {
        return false;
    }
}
