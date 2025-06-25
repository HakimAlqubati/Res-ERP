<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\ResellersCluster;
use App\Filament\Clusters\ResellersCluster\Resources\DeliveredResellerOrdersResource\RelationManagers\PaymentsRelationManager;
use App\Filament\Resources\DeliveredResellerOrdersResource\Pages;
use App\Filament\Resources\DeliveredResellerOrdersResource\RelationManagers;
use App\Models\Branch;
use App\Models\DeliveredResellerOrders;
use App\Models\Order;
use App\Models\Store;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DeliveredResellerOrdersResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = ResellersCluster::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 1;
    public static function getNavigationBadge(): ?string
    {
        return self::getEloquentQuery()->count();
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make()->columnSpanFull()->schema([
                    Grid::make()->columns(3)->schema([
                        Select::make('branch_id')->required()
                            ->label(__('lang.reseller'))
                            ->options(Branch::where('active', 1)->get(['id', 'name'])->pluck('name', 'id')),
                        DatePicker::make('delivered_at')
                            ->label(__('lang.delivered_at'))
                            ->visibleOn('view'),
                        Forms\Components\DateTimePicker::make('created_at')
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
                ])
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->columns([
                TextColumn::make('id')
                    ->label('Order ID')
                    ->searchable()->alignCenter()
                    ->sortable()
                    ->weight(FontWeight::Bold),

                TextColumn::make('branch.name')
                    ->label('Reseller')
                    ->sortable(),

                TextColumn::make('customer.name')
                    ->label('Manager')
                    ->sortable(),

                TextColumn::make('total_amount')
                    ->label('Total Amount')
                    ->numeric()->alignCenter()
                    ->sortable()
                    ->formatStateUsing(fn($state) => formatMoneyWithCurrency($state)),
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
                    }),
                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('delivered_at')
                    ->label('Delivered At')
                    ->sortable()
                    ->state(function ($record) {
                        return optional(
                            $record->logs()
                                ->where('new_status', \App\Models\Order::DELEVIRED)
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
                                ->where('new_status', \App\Models\Order::DELEVIRED)
                                ->latest('created_at')
                                ->with('creator')
                                ->first()
                        )?->creator?->name;
                    }),
            ])
            ->actions([

                Tables\Actions\Action::make('print_delivery_order')
                    ->label(__('Print Delivery Order'))
                    ->icon('heroicon-o-printer')->button()
                    ->color('gray')
                    ->visible(fn($record) => $record->status === Order::DELEVIRED)
                    ->action(function (Order $record) {
                        $record->load(['orderDetails.product', 'branch', 'logs.creator']);

                        $deliveryInfo = $record->getDeliveryInfo();

                        if (!$deliveryInfo) {
                            \Filament\Notifications\Notification::make()
                                ->title('Cannot generate PDF')
                                ->body('Order must be delivered first.')
                                ->danger()
                                ->send();
                            return null;
                        }

                        $pdf = \Mccarlosen\LaravelMpdf\Facades\LaravelMpdf::loadView('export.delivery_order', compact('deliveryInfo'));

                        return response()->streamDownload(
                            fn() => print($pdf->output()),
                            "Delivery Order ({$deliveryInfo['id']}).pdf"
                        );
                    }),

                Tables\Actions\Action::make('add_payment')->button()
                    ->label(__('Add Payment'))
                    ->icon('heroicon-o-banknotes')
                    ->visible(fn(): bool => isSuperAdmin())
                    ->color('success')
                    ->modalHeading('Add Payment to Order')
                    ->form([
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
                    ->visible(fn($record) => $record->status === Order::DELEVIRED),
            ])
            ->defaultSort('id', 'desc');
    }


    public static function getRelations(): array
    {
        return [
            PaymentsRelationManager::class,

        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDeliveredResellerOrders::route('/'),
            'view' => Pages\ViewDeliveredResellerOrders::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('status', Order::DELEVIRED)
            ->whereHas('branch', function ($query) {
                $query->where('type', Branch::TYPE_RESELLER);
            })
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}