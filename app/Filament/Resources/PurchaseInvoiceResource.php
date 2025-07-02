<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\InventoryCluster;
use App\Filament\Clusters\SupplierCluster;
use App\Filament\Clusters\SupplierCluster\Resources\PurchaseInvoiceResource\RelationManagers\DetailsRelationManager;
use App\Filament\Resources\PurchaseInvoiceResource\Pages;
use App\Filament\Resources\PurchaseInvoiceResource\RelationManagers;
use App\Filament\Resources\PurchaseInvoiceResource\RelationManagers\PurchaseInvoiceDetailsRelationManager;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\UnitPrice;
use Closure;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Actions\ActionGroup;
use Filament\Pages\Page;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class PurchaseInvoiceResource extends Resource
{
    protected static ?string $model = PurchaseInvoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $cluster = SupplierCluster::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 2;
    public static function getPluralLabel(): ?string
    {
        return __('lang.purchase_invoice');
    }


    public static function getLabel(): ?string
    {
        return __('lang.purchase_invoice');
    }
    public static function getNavigationLabel(): string
    {
        return __('lang.purchase_invoice');
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make()->schema([
                    Grid::make()->columns(4)->schema([
                        TextInput::make('invoice_no')
                            ->label(__('lang.invoice_no'))
                            ->required(fn(): bool => settingWithDefault('purchase_invoice_no_required_and_disabled_on_edit', false))
                            ->unique(ignoreRecord: true)
                            // ->default(fn(): int => PurchaseInvoice::autoInvoiceNo())
                            ->placeholder('Enter invoice number')
                            ->disabled(function ($record) {
                                $setting = settingWithDefault('purchase_invoice_no_required_and_disabled_on_edit', false);
                                if ($record && $setting) {
                                    return true;
                                }
                                return false;
                            }),
                        DatePicker::make('date')
                            ->required()
                            ->placeholder('Select date')
                            ->default(date('Y-m-d'))
                            ->format('Y-m-d')
                            ->disabledOn('edit')
                            ->format('Y-m-d'),
                        Toggle::make('has_attachment')
                            ->label('Has Attachment')
                            ->inline(false)->live(),
                        Toggle::make('has_description')
                            ->label('Has Description')->inline(false)
                            ->live(),


                    ]),
                    Grid::make()->columns(3)->schema([
                        Select::make('supplier_id')->label(__('lang.supplier'))
                            ->getSearchResultsUsing(fn(string $search): array => Supplier::where('name', 'like', "%{$search}%")->limit(10)->pluck('name', 'id')->toArray())
                            ->getOptionLabelUsing(fn($value): ?string => Supplier::find($value)?->name)
                            ->searchable()
                            ->options(Supplier::limit(5)->get(['id', 'name'])->pluck('name', 'id'))
                            ->disabledOn('edit'),

                        Select::make('store_id')->label(__('lang.store'))
                            ->searchable()
                            ->disabledOn('edit')->required()
                            ->default(getDefaultStore())
                            ->options(
                                Store::where('active', 1)
                                    ->withManagedStores()
                                    ->get(['id', 'name'])->pluck('name', 'id')
                            )
                            ->disabledOn('edit')
                            ->searchable(),
                        Select::make('payment_method_id')
                            ->label('Payment Method')
                            ->relationship('paymentMethod', 'name')
                            ->searchable()
                            ->preload()
                    ]),
                    Textarea::make('cancel_reason')->label('Cancel Reason')
                        ->placeholder('Cancel Reason')->hiddenOn('create')
                        ->visible(fn($record): bool => $record->cancelled)->readOnly()
                        ->columnSpanFull(),
                    Textarea::make('description')->label(__('lang.description'))
                        ->placeholder('Enter description')->visible(fn($get): bool => $get('has_description'))
                        ->columnSpanFull(),
                    FileUpload::make('attachment')
                        ->label(__('lang.attachment'))
                        // ->enableOpen()
                        // ->enableDownload()
                        ->directory('purchase-invoices')->visible(fn($get): bool => $get('has_attachment'))
                        ->columnSpanFull()
                        ->acceptedFileTypes(['application/pdf'])
                        ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                            return (string) str($file->getClientOriginalName())->prepend('purchase-invoice-');
                        })->hiddenOn('view'),
                    Repeater::make('units')->hiddenOn(['view', 'edit'])
                        ->createItemButtonLabel(__('lang.add_item'))
                        ->columns(9)
                        ->defaultItems(1)
                        ->deletable(function ($record) {
                            if (is_null($record)) {
                                return true;
                            }
                            return false;
                        })
                        ->columnSpanFull()
                        ->collapsible()
                        ->relationship('purchaseInvoiceDetails')
                        ->label(__('lang.purchase_invoice_details'))
                        ->schema([
                            Select::make('product_id')
                                ->label(__('lang.product'))
                                ->searchable()
                                ->distinct()
                                ->disabledOn('edit')
                                ->options(function () {
                                    return Product::where('active', 1)
                                        ->unmanufacturingCategory()
                                        ->orderBy('id', 'asc')
                                        ->get(['id', 'code', 'name', 'active'])

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
                                ->disabledOn('edit')
                                ->options(function (callable $get) {
                                    $product = \App\Models\Product::find($get('product_id'));
                                    if (! $product) return [];

                                    return $product->unitPrices->pluck('unit.name', 'unit_id')->toArray();
                                })
                                ->searchable()
                                ->reactive()
                                ->afterStateUpdated(function (\Filament\Forms\Set $set, $state, $get) {
                                    $unitPrice = UnitPrice::where(
                                        'product_id',
                                        $get('product_id')
                                    )
                                        ->showInInvoices()
                                        ->where('unit_id', $state)->first();
                                    $set('price', $unitPrice->price ?? 0);
                                    $total = round(((float) ($unitPrice->price ?? 0)) * ((float) $get('quantity')), 2) ?? 0;

                                    $set('total_price', $total ?? 0);
                                    $set('package_size',  $unitPrice->package_size ?? 0);
                                })->columnSpan(2)->required(),
                            TextInput::make('package_size')->type('number')->readOnly()->columnSpan(1)
                                ->label(__('lang.package_size')),
                            TextInput::make('quantity')
                                ->label(__('lang.quantity'))

                                ->numeric()

                                ->minValue(0.1)
                                ->default(1)
                                ->disabledOn('edit')
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (\Filament\Forms\Set $set, $state, $get) {
                                    $total = round(((float) $state) * ((float)$get('price') ?? 0), 2);
                                    $set('total_price', $total);
                                })->columnSpan(1)->required(),
                            TextInput::make('price')
                                ->label(__('lang.price'))
                                ->type('text')
                                ->minValue(1)
                                // ->integer()
                                ->disabledOn('edit')
                                ->live(onBlur: true)

                                ->afterStateUpdated(function (\Filament\Forms\Set $set, $state, $get) {
                                    $total = round(((float) $state) * ((float)$get('quantity')), 2);
                                    $set('total_price', $total);
                                })->columnSpan(1)->required(),
                            TextInput::make('total_price')->minValue(1)->label('Total Price')
                                ->numeric()
                                ->extraInputAttributes(['readonly' => true])->columnSpan(1),
                            TextInput::make('waste_stock_percentage')
                                ->label(__('lang.waste_stock_percentage'))
                                ->suffix('%')
                                ->type('number')
                                ->minValue(0)
                                ->maxValue(100)
                                ->default(function (callable $get) {
                                    $product = \App\Models\Product::find($get('product_id'));
                                    return $product?->waste_stock_percentage ?? 0;
                                })
                                ->live(onBlur: true)
                                ->columnSpan(1)
                                ->required(),


                        ])
                ])
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->color('primary')
                    ->weight(FontWeight::Bold)->alignCenter(true)
                    ->searchable(isIndividual: true)->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('invoice_no')
                    ->color('primary')->copyable()
                    ->weight(FontWeight::Bold)->alignCenter(true)
                    ->searchable()->sortable()->toggleable(),
                TextColumn::make('supplier.name')->label('Supplier')->toggleable()->default('-')->wrap(),
                TextColumn::make('store.name')->label('Store')->toggleable(),
                TextColumn::make('date')->sortable()->toggleable(),
                TextColumn::make('description')->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('details_count')->alignCenter(true)
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('total_amount')
                    ->label(__('lang.total_amount'))
                    ->alignCenter(true)
                    ->formatStateUsing(function ($state) {
                        return formatMoneyWithCurrency($state);
                    })
                    ->summarize(
                        Summarizer::make()
                            ->using(function (\Filament\Tables\Table $table) {
                                $total  = $table->getRecords()->sum(fn($record) => $record->total_amount);
                                if (is_numeric($total)) {
                                    return formatMoneyWithCurrency($total);
                                }
                                return $total;
                            })
                    )
                    ->toggleable(isToggledHiddenByDefault: false),
                IconColumn::make('has_attachment')->alignCenter(true)->label(__('lang.has_attachment'))
                    ->boolean()->toggleable()
                // ->trueIcon('heroicon-o-badge-check')
                // ->falseIcon('heroicon-o-x-circle')
                ,
                IconColumn::make('has_grn')->alignCenter(true)->label(__('lang.has_grn'))->boolean()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('grn.grn_number')
                    ->label('GRN Number')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('paymentMethod.name')
                    ->label('Payment Method')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                IconColumn::make('has_inventory_transaction')
                    ->label('Inventory Updated')
                    ->boolean()->toggleable(isToggledHiddenByDefault: true)
                    ->alignCenter(),
                TextColumn::make('creator_name')
                    ->label('Creator')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Created At')->date('Y-m-d')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('date')
                    ->label('Date')->date('Y-m-d')
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('has_outbound_transactions')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Has Outbound')->boolean()->alignCenter(),
                IconColumn::make('cancelled')
                    ->label('Cancelled')->toggleable(isToggledHiddenByDefault: true)->boolean()->alignCenter(),

            ])
            ->filters([
                // Tables\Filters\TrashedFilter::make(),

                SelectFilter::make('id')
                    ->label('ID')
                    ->multiple() // 2. تمت إضافة إمكانية الاختيار المتعدد
                    ->searchable() // 3. تمت إضافة إمكانية البحث
                    ->getSearchResultsUsing(function (string $search): array {
                        // هذه الدالة تبحث برقم الفاتورة أو بال ID الرقمي
                        return PurchaseInvoice::where('invoice_no', 'like', "%{$search}%")
                            ->orWhere('id', $search)
                            ->limit(50)
                            ->pluck('id', 'id')
                            ->toArray();
                    })
                    ->getOptionLabelUsing(function ($value): ?string {
                        // هذه الدالة تعرض رقم الفاتورة بعد اختيارها
                        return PurchaseInvoice::find($value)?->id;
                    }),
                SelectFilter::make('payment_method_id')
                    ->label('Payment Method')
                    ->options(PaymentMethod::active()->get()->pluck('name', 'id')),
                SelectFilter::make('supplier_id')
                    ->label('Supplier')
                    ->options(Supplier::get()->pluck('name', 'id')),
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        DatePicker::make('from')->label('From Date'),
                        DatePicker::make('to')->label('To Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn($q, $date) => $q->whereDate('date', '>=', $date))
                            ->when($data['to'], fn($q, $date) => $q->whereDate('date', '<=', $date));
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

            ], FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('create_inventory')
                        ->label('Create Inventory')
                        ->icon('heroicon-o-plus-circle')->button()
                        ->color('success')
                        ->visible(fn($record) => !$record->has_inventory_transaction)
                        ->action(function ($record) {
                            DB::beginTransaction();
                            try {
                                foreach ($record->details as $detail) {
                                    \App\Models\InventoryTransaction::moveToStore([
                                        'product_id' => $detail->product_id,
                                        'movement_type' => \App\Models\InventoryTransaction::MOVEMENT_IN,
                                        'quantity' => $detail->quantity,
                                        'unit_id' => $detail->unit_id,
                                        'package_size' => $detail->package_size,
                                        'store_id' => $record->store_id,
                                        'price' => $detail->price,
                                        'transaction_date' => $record->date,
                                        'movement_date' => $record->date,
                                        'notes' => 'Purchase invoice with id #' . $record->id . ' ' . $record->store->name ?? '',
                                        'transactionable' => $record,
                                    ]);
                                }
                                DB::commit();
                                showSuccessNotifiMessage('Done');
                            } catch (\Exception $e) {
                                DB::rollBack();
                                showWarningNotifiMessage($e->getMessage());
                            }
                        })->hidden(),
                    Tables\Actions\EditAction::make()
                        ->icon('heroicon-s-pencil'),
                    Tables\Actions\Action::make('download')
                        ->label(__('lang.download_attachment'))
                        ->action(function ($record) {
                            if (strlen($record['attachment']) > 0) {
                                if (env('APP_ENV') == 'local') {
                                    $file_link = url('storage/' . $record['attachment']);
                                } else if (env('APP_ENV') == 'production') {
                                    $file_link = url('New-Res-System/public/storage/' . $record['attachment']);
                                }
                                return redirect(url($file_link));
                            }
                        })->hidden(fn($record) => !(strlen($record['attachment']) > 0))
                        // ->icon('heroicon-o-download')
                        ->color('green'),
                    Tables\Actions\Action::make('cancel')
                        ->label('Cancel')->hidden(fn($record): bool => $record->cancelled)
                        ->icon('heroicon-o-backspace')->button()->color(Color::Red)
                        ->form([
                            Textarea::make('cancel_reason')->required()->label('Cancel Reason')
                        ])
                        ->action(function ($record, $data) {
                            try {
                                $result = $record->handleCancellation($record, $data['cancel_reason']);

                                if ($result['status'] === 'success') {
                                    \Filament\Notifications\Notification::make()
                                        ->title('Success')
                                        ->body($result['message'])
                                        ->success()
                                        ->send();
                                } else {
                                    \Filament\Notifications\Notification::make()
                                        ->title('Error')
                                        ->body($result['message'])
                                        ->danger()
                                        ->send();
                                }
                            } catch (\Throwable $th) {
                                throw $th;
                            }
                        })->hidden(fn(): bool => isSuperVisor())
                ]),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\RestoreBulkAction::make()
            ]);
    }


    public static function getRelations(): array
    {
        return [
            // PurchaseInvoiceDetailsRelationManager::class,
            DetailsRelationManager::class,
        ];
    }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseInvoices::route('/'),
            'create' => Pages\CreatePurchaseInvoice::route('/create'),
            'edit' => Pages\EditPurchaseInvoice::route('/{record}/edit'),
            'view' => Pages\ViewPurchaseInvoice::route('/{record}'),
        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\ListPurchaseInvoices::class,
            Pages\CreatePurchaseInvoice::class,
            Pages\EditPurchaseInvoice::class,
            Pages\ViewPurchaseInvoice::class,
        ]);
    }

    public static function canDeleteAny(): bool
    {
        return false;
        if (isSuperAdmin()) {
            return true;
        }
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutTrashed()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
        // $query->withDetails();
        return $query;
    }
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
    public static function canCreate(): bool
    {
        // if (settingWithDefault('purchase_invoice_from_grn_only', false)) {
        //     return false;
        // }
        if (isSuperAdmin() || isFinanceManager()) {
            return true;
        }
        if (isSuperVisor() || isStoreManager()) {
            return false;
        }

        return static::can('create');
    }


    public static function canEdit(Model $record): bool
    {
        if (isSuperVisor()) {
            return false;
        }
        return static::can('update', $record);
    }
}