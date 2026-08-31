<?php

namespace App\Filament\Clusters\SupplierCluster\Resources;

use App\Filament\Clusters\SupplierCluster;
use App\Filament\Clusters\SupplierCluster\Resources\PurchaseReturnResource\Pages\CreatePurchaseReturn;
use App\Filament\Clusters\SupplierCluster\Resources\PurchaseReturnResource\Pages\EditPurchaseReturn;
use App\Filament\Clusters\SupplierCluster\Resources\PurchaseReturnResource\Pages\ListPurchaseReturns;
use App\Filament\Clusters\SupplierCluster\Resources\PurchaseReturnResource\Pages\ViewPurchaseReturn;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\Unit;
use App\Modules\Stock\PurchaseReturns\Actions\ApprovePurchaseReturnAction;
use App\Modules\Stock\PurchaseReturns\Actions\CancelPurchaseReturnAction;
use App\Modules\Stock\PurchaseReturns\Queries\GetInvoiceReturnableItemsQuery;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

class PurchaseReturnResource extends Resource
{
    protected static ?string $model = PurchaseReturn::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::ArrowUturnLeft;

    protected static ?string $cluster = SupplierCluster::class;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'return_no';

    public static function getNavigationLabel(): string
    {
        return 'Purchase Returns';
    }

    public static function getPluralLabel(): ?string
    {
        return 'Purchase Returns';
    }

    public static function getLabel(): ?string
    {
        return 'Purchase Return';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('General Information')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('return_no')
                                ->label('Return Number')
                                ->default(fn() => PurchaseReturn::autoReturnNo())
                                ->readOnly()
                                ->required(),

                            DatePicker::make('return_date')
                                ->label('Return Date')
                                ->default(date('Y-m-d'))
                                ->required(),

                            Select::make('purchase_invoice_id')
                                ->label('Original Purchase Invoice')
                                ->options(function () {
                                    return PurchaseInvoice::query()
                                        ->where('cancelled', false)
                                        ->orderBy('id', 'desc')
                                        ->limit(100)
                                        ->pluck('invoice_no', 'id');
                                })
                                ->searchable()
                                ->live()
                                ->afterStateUpdated(function ($state, Set $set) {
                                    if ($state) {
                                        $query = app(GetInvoiceReturnableItemsQuery::class);
                                        $data = $query->execute((int) $state);

                                        $set('supplier_id', $data['supplier_id']);
                                        $set('store_id', $data['store_id']);
                                        $set('details', $data['items']);

                                        $total = collect($data['items'])->sum(fn($row) => (float) ($row['total_price'] ?? 0));
                                        $set('total_amount', round($total, 4));
                                    }
                                }),
                        ]),

                        Grid::make(3)->schema([
                            Select::make('supplier_id')
                                ->label('Supplier')
                                ->options(Supplier::pluck('name', 'id'))
                                ->searchable()
                                ->required(),

                            Select::make('store_id')
                                ->label('Store')
                                ->options(Store::where('active', 1)->pluck('name', 'id'))
                                ->searchable()
                                ->required(),

                            Select::make('payment_method_id')
                                ->label('Payment / Refund Method')
                                ->options(PaymentMethod::pluck('name', 'id'))
                                ->searchable(),
                        ]),

                        Grid::make(2)->schema([
                            TextInput::make('total_amount')
                                ->label('Total Amount')
                                ->numeric()
                                ->readOnly()
                                ->default(0),

                            FileUpload::make('attachment')
                                ->label('Attachment')
                                ->directory('purchase-returns')
                                ->downloadable(),
                        ]),

                        Textarea::make('reason')
                            ->label('Return Reason')
                            ->placeholder('Specify why items are being returned to the supplier')
                            ->columnSpanFull(),

                        Textarea::make('notes')
                            ->label('Additional Notes')
                            ->columnSpanFull(),
                    ]),

                Section::make('Return Items Details')
                    ->schema([
                        Repeater::make('details')
                            ->label('Return Line Items')
                            ->relationship('details')
                            ->defaultItems(0)
                            ->columns(8)
                            ->table([
                                TableColumn::make('Product')->width('20rem'),
                                TableColumn::make('Unit')->alignCenter()->width('10rem'),
                                TableColumn::make('Package Size')->alignCenter()->width('8rem'),
                                TableColumn::make('Return Quantity')->alignCenter()->width('10rem'),
                                TableColumn::make('Unit Price')->alignCenter()->width('10rem'),
                                TableColumn::make('Total Price')->alignCenter()->width('10rem'),
                                TableColumn::make('Notes')->width('14rem'),
                            ])
                            ->schema([
                                Hidden::make('purchase_invoice_detail_id'),

                                Select::make('product_id')
                                    ->label('Product')
                                    ->options(Product::where('active', 1)->pluck('name', 'id'))
                                    ->searchable()
                                    ->required()
                                    ->columnSpan(2),

                                Select::make('unit_id')
                                    ->label('Unit')
                                    ->options(Unit::pluck('name', 'id'))
                                    ->searchable()
                                    ->required()
                                    ->columnSpan(1),

                                TextInput::make('package_size')
                                    ->label('Package Size')
                                    ->numeric()
                                    ->default(1)
                                    ->readOnly()
                                    ->columnSpan(1),

                                TextInput::make('quantity')
                                    ->label('Quantity')
                                    ->numeric()
                                    ->minValue(0.0001)
                                    ->default(1)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Set $set, $state, Get $get) {
                                        $qty = (float) $state;
                                        $price = (float) ($get('unit_price') ?? 0);
                                        $total = round($qty * $price, 4);
                                        $set('total_price', $total);

                                        // Recalculate header total
                                        $rows = $get('../../details') ?? [];
                                        $sum = collect($rows)->sum(fn($r) => (float) ($r['total_price'] ?? 0));
                                        $set('../../total_amount', round($sum, 4));
                                    })
                                    ->required()
                                    ->columnSpan(1),

                                TextInput::make('unit_price')
                                    ->label('Unit Price')
                                    ->numeric()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Set $set, $state, Get $get) {
                                        $price = (float) $state;
                                        $qty = (float) ($get('quantity') ?? 0);
                                        $total = round($qty * $price, 4);
                                        $set('total_price', $total);

                                        $rows = $get('../../details') ?? [];
                                        $sum = collect($rows)->sum(fn($r) => (float) ($r['total_price'] ?? 0));
                                        $set('../../total_amount', round($sum, 4));
                                    })
                                    ->required()
                                    ->columnSpan(1),

                                TextInput::make('total_price')
                                    ->label('Total')
                                    ->numeric()
                                    ->readOnly()
                                    ->columnSpan(1),

                                TextInput::make('notes')
                                    ->label('Notes')
                                    ->columnSpan(1),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->striped()
            ->defaultSort('id', 'desc')
            ->recordUrl(fn(PurchaseReturn $record): string => static::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('return_no')
                    ->label('Return No')
                    ->weight(FontWeight::Bold)
                    ->color('primary')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('return_date')
                    ->label('Return Date')
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('purchaseInvoice.invoice_no')
                    ->label('Invoice No')
                    ->default('-')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('store.name')
                    ->label('Store')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        PurchaseReturn::STATUS_DRAFT     => 'warning',
                        PurchaseReturn::STATUS_APPROVED  => 'success',
                        PurchaseReturn::STATUS_REJECTED  => 'danger',
                        PurchaseReturn::STATUS_CANCELLED => 'gray',
                        default                          => 'info',
                    })
                    ->formatStateUsing(fn(string $state): string => ucfirst($state)),

                TextColumn::make('total_amount')
                    ->label('Total Amount')
                    ->alignEnd()
                    ->formatStateUsing(fn($state) => formatMoneyWithCurrency($state))
                    ->summarize(
                        Summarizer::make()
                            ->using(fn(Table $t) => formatMoneyWithCurrency($t->getRecords()->sum('total_amount')))
                    ),

                TextColumn::make('details_count')
                    ->label('Items')
                    ->alignCenter(),

                TextColumn::make('creator_name')
                    ->label('Created By')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('Y-m-d H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(PurchaseReturn::getStatusOptions()),

                SelectFilter::make('supplier_id')
                    ->label('Supplier')
                    ->options(Supplier::pluck('name', 'id'))
                    ->searchable(),

                SelectFilter::make('store_id')
                    ->label('Store')
                    ->options(Store::where('active', 1)->pluck('name', 'id'))
                    ->searchable(),

                Filter::make('return_date_range')
                    ->schema([
                        DatePicker::make('from')->label('From Date'),
                        DatePicker::make('to')->label('To Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn($q, $date) => $q->whereDate('return_date', '>=', $date))
                            ->when($data['to'], fn($q, $date) => $q->whereDate('return_date', '<=', $date));
                    }),
            ], FiltersLayout::Modal)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make()
                        ->visible(fn(PurchaseReturn $record) => $record->status === PurchaseReturn::STATUS_DRAFT && !$record->cancelled),

                    Action::make('approve')
                        ->label('Approve Return')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn(PurchaseReturn $record) => $record->status === PurchaseReturn::STATUS_DRAFT && !$record->cancelled)
                        ->requiresConfirmation()
                        ->modalHeading('Approve Purchase Return')
                        ->modalDescription('This will deduct items from inventory and credit supplier balance. Proceed?')
                        ->action(function (PurchaseReturn $record, ApprovePurchaseReturnAction $action) {
                            try {
                                $action->execute($record, (int) auth()->id());
                                Notification::make()
                                    ->title('Approved')
                                    ->body('Purchase return has been approved successfully.')
                                    ->success()
                                    ->send();
                            } catch (Throwable $e) {
                                Notification::make()
                                    ->title('Error')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),

                    Action::make('cancel')
                        ->label('Cancel Return')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn(PurchaseReturn $record) => !$record->cancelled)
                        ->form([
                            Textarea::make('cancel_reason')->label('Cancellation Reason')->required(),
                        ])
                        ->action(function (PurchaseReturn $record, array $data, CancelPurchaseReturnAction $action) {
                            try {
                                $action->execute($record, $data['cancel_reason'], (int) auth()->id());
                                Notification::make()
                                    ->title('Cancelled')
                                    ->body('Purchase return has been cancelled.')
                                    ->warning()
                                    ->send();
                            } catch (Throwable $e) {
                                Notification::make()
                                    ->title('Error')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListPurchaseReturns::route('/'),
            'create' => CreatePurchaseReturn::route('/create'),
            'edit'   => EditPurchaseReturn::route('/{record}/edit'),
            'view'   => ViewPurchaseReturn::route('/{record}'),
        ];
    }
}
