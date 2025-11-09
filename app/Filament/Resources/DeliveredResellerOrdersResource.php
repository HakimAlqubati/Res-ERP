<?php

namespace App\Filament\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\DateTimePicker;
use Filament\Actions\Action;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf;
use App\Filament\Resources\DeliveredResellerOrdersResource\Pages\ListDeliveredResellerOrders;
use App\Filament\Resources\DeliveredResellerOrdersResource\Pages\ViewDeliveredResellerOrders;
use App\Filament\Clusters\HRServiceRequestCluster\Resources\ServiceRequestResource\RelationManagers\LogsRelationManager;
use App\Filament\Clusters\ResellersCluster;
use App\Filament\Clusters\ResellersCluster\Resources\DeliveredResellerOrdersResource\RelationManagers\PaymentsRelationManager;
use App\Filament\Resources\DeliveredResellerOrdersResource\Pages;
use App\Filament\Resources\DeliveredResellerOrdersResource\Pages\CreateDeliveredResellerOrder;
use App\Filament\Resources\DeliveredResellerOrdersResource\RelationManagers;
use App\Filament\Resources\OrderResource\RelationManagers\OrderDetailsRelationManager;
use App\Models\Branch;
use App\Models\DeliveredResellerOrders;
use App\Models\Order;
use App\Models\Store;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;

class DeliveredResellerOrdersResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = ResellersCluster::class;
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 1;
    public static function getNavigationBadge(): ?string
    {
        return Order::query()
            ->whereHas('branch', function ($query) {
                $query->where('type', Branch::TYPE_RESELLER);
            })->forBranchManager()->count();
    }

    public static function getPluralLabel(): ?string
    {
        return __('lang.delivery_orders');
    }
    public static function getPluralModelLabel(): string
    {
        return __('lang.delivery_orders');
    }

    public static function getLabel(): ?string
    {
        return __('lang.delivery_order');
    }
    public static function getModelLabel(): string
    {
        return __('lang.delivery_order');
    }
    public static function getNavigationLabel(): string
    {
        return __('lang.delivery_orders');
    }


    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Wizard::make([
                // ـــــــــــــــــــــــــــــــــــــــــــــــــــــ
                // الخطوة 1: المعلومات الأساسية
                // ـــــــــــــــــــــــــــــــــــــــــــــــــــــ
                Wizard\Step::make('Basic')->columnSpanFull()
                    ->schema([
                        \Filament\Schemas\Components\Fieldset::make(__('lang.delivery_orders'))
                            ->columnSpanFull()
                            ->schema([
                                \Filament\Schemas\Components\Grid::make()->columns(2)
                                    ->columnSpanFull()
                                    ->schema([
                                        \Filament\Forms\Components\Select::make('branch_id')
                                            ->label(__('lang.reseller'))
                                            ->required()
                                            ->searchable()
                                            ->options(
                                                \App\Models\Branch::active()
                                                    ->resellers()
                                                    ->get(['id', 'name'])
                                                    ->pluck('name', 'id')
                                            )
                                            ->live()
                                            ->afterStateUpdated(function (Set $set, $state) {
                                                $managerId = Branch::find($state)?->manager_id ?? null;
                                                $set('customer_id', $managerId);
                                            }),
                                        Hidden::make('customer_id')->required(),

                                        \Filament\Forms\Components\Select::make('status')
                                            ->label('Status')
                                            ->required()
                                            ->options(\App\Models\Order::getStatusLabels())
                                            ->disabled()
                                            ->dehydrated()
                                            ->default(\App\Models\Order::ORDERED),

                                    ]),

                                \Filament\Forms\Components\Select::make('stores')
                                    ->label(__('lang.store'))
                                    ->multiple()
                                    ->options(
                                        \App\Models\Store::active()->get()->pluck('name', 'id')->toArray()
                                    )
                                    ->hidden(),

                                \Filament\Forms\Components\Textarea::make('notes')
                                    ->label(__('lang.notes'))
                                    ->rows(3)
                                    ->maxLength(1000)
                                    ->columnSpanFull(),
                            ]),
                    ]),

                // ـــــــــــــــــــــــــــــــــــــــــــــــــــــ
                // الخطوة 2: المنتجات + الإجماليات
                // ـــــــــــــــــــــــــــــــــــــــــــــــــــــ
                Wizard\Step::make(__('lang.products'))
                    ->columnSpanFull()
                    ->schema([

                        \Filament\Forms\Components\Repeater::make('orderDetails')
                            ->label(__('lang.order_details'))
                            ->relationship('orderDetails')
                            ->columnSpanFull()
                            ->defaultItems(1)
                            ->minItems(1)
                            ->reorderable()
                            ->columns(4)
                            ->addActionLabel(__('lang.add_item'))
                            ->schema([
                                // المنتج
                                \Filament\Forms\Components\Select::make('product_id')
                                    ->label(__('lang.product'))
                                    ->searchable()
                                    ->options(function () {
                                        return \App\Models\Product::where('active', 1)
                                            ->manufacturingCategory()
                                            ->pluck('name', 'id');
                                    })
                                    ->getSearchResultsUsing(
                                        fn(string $search): array =>
                                        \App\Models\Product::where('active', 1)
                                            ->manufacturingCategory()
                                            ->where('name', 'like', "%{$search}%")
                                            ->limit(50)->pluck('name', 'id')->toArray()
                                    )
                                    ->getOptionLabelUsing(
                                        fn($value): ?string =>
                                        \App\Models\Product::manufacturingCategory()->find($value)?->name
                                    )
                                    ->reactive()
                                    ->afterStateUpdated(function (callable $set) {
                                        // إعادة ضبط الوحدة والسعر عند تغيير المنتج
                                        $set('unit_id', null);
                                        $set('price', 0);
                                        $set('package_size', 0);
                                        $set('total_price', 0);
                                    })
                                    ->columnSpan(2)
                                    ->required(),

                                // الوحدة
                                \Filament\Forms\Components\Select::make('unit_id')
                                    ->label(__('lang.unit'))
                                    ->options(function (callable $get) {
                                        $unitPrices = \App\Models\UnitPrice::where('product_id', $get('product_id'))
                                            ->get()->toArray();
                                        return $unitPrices ? array_column($unitPrices, 'unit_name', 'unit_id') : [];
                                    })
                                    ->searchable()
                                    ->reactive()
                                    ->afterStateUpdated(function (Set $set, $state, Get $get) {
                                        $unitPrice = \App\Models\UnitPrice::where('product_id', $get('product_id'))
                                            ->where('unit_id', $state)->first();
                                        $price = (float) ($unitPrice->price ?? 0);
                                        $qty   = (float) ($get('quantity') ?? 1);

                                        $set('price', $price);
                                        $set('package_size', (float) ($unitPrice->package_size ?? 0));
                                        $set('total_price', max(0, ($qty * $price)));
                                    })
                                    ->required(),

                                // حجم العبوة (للعرض)

                                Hidden::make('package_size'),
                                // الكمية
                                \Filament\Forms\Components\TextInput::make('quantity')
                                    ->label(__('lang.quantity'))
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(1)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Set $set, $state, Get $get) {
                                        $qty   = (float) ($state ?? 0);
                                        $price = (float) ($get('price') ?? 0);
                                        $set('total_price', max(0, ($qty * $price)));
                                    })
                                    ->required(),

                                // السعر للوحدة المختارة (يُملأ تلقائياً)

                                Hidden::make('price'),
                                Hidden::make('total_price'),
                            ]),
                    ]),
            ])
                ->columnSpanFull()
                ->skippable(), // لا تحفظ الخطوة في الرابط
        ]);
    }


    public static function table(Table $table): Table
    {
        return $table->deferFilters(false)
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->columns([
                TextColumn::make('id')
                    ->label('DO-ID')
                    ->searchable()->alignCenter()
                    ->sortable()->copyable()
                    ->weight(FontWeight::Bold),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'primary',
                        'secondary' => static fn($state): bool => $state === Order::PENDING_APPROVAL,
                        'warning' => static fn($state): bool => $state === Order::READY_FOR_DELEVIRY,
                        'success' => static fn($state): bool => $state === Order::DELEVIRED,
                        'danger' => static fn($state): bool => $state === Order::PROCESSING,
                    ])
                    ->iconPosition('after')->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('branch.name')
                    ->label('Reseller')
                    ->sortable(),

                TextColumn::make('customer.name')
                    ->label('Manager')
                    ->sortable(),
                TextColumn::make('item_count')->label(__('lang.item_counts'))->alignCenter(true),
                // TextColumn::make('total_amount')
                //     ->label('Total Amount')
                //     ->numeric()->alignCenter()
                //     ->sortable()
                //     ->formatStateUsing(fn($state) => formatMoneyWithCurrency($state))->hidden(),
                // TextColumn::make('total_returned_amount')
                //     ->label('Total Returned')
                //     ->numeric()->alignCenter()
                //     ->sortable()
                //     ->formatStateUsing(fn($state) => formatMoneyWithCurrency($state)),
                TextColumn::make('total_paid')
                    ->alignCenter()
                    ->formatStateUsing(function ($state) {
                        return formatMoneyWithCurrency($state);
                    })
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->sortable(),

                TextColumn::make('balance_due')
                    ->label(__('Remaining'))
                    ->alignCenter()
                    ->sortable()
                    ->formatStateUsing(function ($state) {
                        return formatMoneyWithCurrency($state);
                    })->hidden(),
                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('delivered_at')
                    ->label(__('lang.delivered_at'))
                    ->sortable()->toggleable(isToggledHiddenByDefault: true)
                    ->state(function ($record) {
                        return optional(
                            $record->logs()
                                ->where('new_status', Order::DELEVIRED)
                                ->latest('created_at')
                                ->first()
                        )?->created_at;
                    })
                    ->dateTime(),
                TextColumn::make('delivered_by')
                    ->label('Delivered By')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->state(function ($record) {
                        return optional(
                            $record->logs()
                                ->where('new_status', Order::DELEVIRED)
                                ->latest('created_at')
                                ->with('creator')
                                ->first()
                        )?->creator?->name;
                    }),
            ])
            ->recordActions([

                Action::make('print_delivery_order')
                    ->label(__('Print Delivery Order'))
                    ->icon('heroicon-o-printer')->button()
                    ->color('gray')
                    // ->visible(fn($record) => $record->status === Order::DELEVIRED)
                    ->action(function (Order $record) {
                        $record->load(['orderDetails.product', 'branch', 'logs.creator']);

                        $deliveryInfo = $record->getDeliveryInfo();

                        // if (!$deliveryInfo) {
                        //     \Filament\Notifications\Notification::make()
                        //         ->title('Cannot generate PDF')
                        //         ->body('Order must be delivered first.')
                        //         ->danger()
                        //         ->send();
                        //     return null;
                        // }

                        $pdf = LaravelMpdf::loadView('export.delivery_order', compact('deliveryInfo'));

                        return response()->streamDownload(
                            fn() => print($pdf->output()),
                            "Delivery Order ({$deliveryInfo['id']}).pdf"
                        );
                    }),

                EditAction::make()->label(__('Edit'))
                    ->icon(Heroicon::Pencil)
                    ->color(Color::Green)->button()
                    // ->requiresConfirmation()
                    ->visible(fn(Order $record): bool => !in_array($record->status, [
                        Order::DELEVIRED,
                        Order::READY_FOR_DELEVIRY
                    ])),
                Action::make('approve')
                    ->label(__('Approve'))
                    ->icon(Heroicon::CheckCircle)
                    ->color(Color::Green)
                    ->button()
                    ->requiresConfirmation()
                    ->visible(fn(Order $record): bool => ! in_array($record->status, [
                        Order::DELEVIRED,
                        Order::READY_FOR_DELEVIRY,
                    ], true))
                    ->databaseTransaction()
                    ->action(function (Order $record): void {
                        try {
                            // نغلق السجل لمنع التحديث المتوازي
                            $order = Order::query()
                                ->whereKey($record->getKey())
                                ->lockForUpdate()
                                ->firstOrFail();

                            // تحقق من الحالة
                            if (in_array($order->status, [Order::DELEVIRED, Order::READY_FOR_DELEVIRY], true)) {
                                throw new \Exception(__('The order is already processed.'));
                            }

                            // التحديث
                            $order->update([
                                'status' => Order::READY_FOR_DELEVIRY,
                            ]);

                            // رسالة النجاح
                            \Filament\Notifications\Notification::make()
                                ->title(__('Order approved successfully'))
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            // نعرض الخطأ للمستخدم
                            \Filament\Notifications\Notification::make()
                                ->title(__('Error occurred'))
                                ->body($e->getMessage()) // تعرض تفاصيل الخطأ إن أردت
                                ->danger()
                                ->send();

                            // نرمي الاستثناء مجددًا عشان يرجع rollback من Filament
                            throw $e;
                        }
                    }),

                Action::make('add_payment')->button()
                    ->label(__('Add Payment'))
                    ->icon('heroicon-o-banknotes')
                    ->visible(fn(): bool => isSuperAdmin())
                    ->color('success')
                    ->modalHeading('Add Payment to Order')
                    ->schema([
                        Fieldset::make()->columns(2)->schema([
                            TextInput::make('amount')
                                ->label('Amount')
                                ->required()
                                ->maxValue(function ($record) {
                                    return $record->balance_due;
                                })->placeholder(function ($record) {
                                    return $record->balance_due;
                                })
                                ->numeric()
                                ->minValue(0.01)
                                ->prefixIcon('heroicon-o-banknotes'),
                            DatePicker::make('paid_at')
                                ->label('Paid At')
                                ->prefixIcon('heroicon-o-calendar-days')
                                ->default(now())
                                ->required(),
                        ]),
                        Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->maxLength(500)
                            ->nullable(),
                    ])
                    ->action(function ($record, array $data) {
                        $record->paidAmounts()->create([
                            'amount' => $data['amount'],
                            'paid_at' => $data['paid_at'],
                            'notes' => $data['notes'],
                            'created_by' => auth()->id(),
                        ]);
                        showSuccessNotifiMessage('Done');
                    })
                    ->visible(fn($record) => $record->status === Order::DELEVIRED)
                    ->hidden()
                    ,
            ])
            ->filters([
                SelectFilter::make('branch_id')
                    ->label('Reseller')->searchable()
                    ->options(Branch::active()->resellers()->get(['id', 'name'])->pluck('name', 'id')),
            ], FiltersLayout::AboveContent)
            ->defaultSort('id', 'desc');
    }


    public static function getRelations(): array
    {
        return [
            OrderDetailsRelationManager::class,
            PaymentsRelationManager::class,
            LogsRelationManager::class,

        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDeliveredResellerOrders::route('/'),
            'view' => ViewDeliveredResellerOrders::route('/{record}'),
            // 'create' => CreateDeliveredResellerOrder::route('/create'),
        ];
    }

    // public static function canCreate(): bool
    // {

    //     if (isSystemManager() || isSuperAdmin()) {
    //         return true;
    //     }
    //     return false;
    // }


    public static function getEloquentQuery(): Builder
    {
        return Order::query()
            // ->where('status', Order::DELEVIRED)
            ->forBranchManager()
            ->where('is_purchased', 0)
            ->whereHas('orderDetails')
            ->whereHas('branch', function ($query) {
                $query->where('type', Branch::TYPE_RESELLER);
            })
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
