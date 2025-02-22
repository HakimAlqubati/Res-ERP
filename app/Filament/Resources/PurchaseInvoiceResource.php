<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\InventoryCluster;
use App\Filament\Clusters\SupplierCluster;
use App\Filament\Resources\PurchaseInvoiceResource\Pages;
use App\Filament\Resources\PurchaseInvoiceResource\RelationManagers;
use App\Filament\Resources\PurchaseInvoiceResource\RelationManagers\PurchaseInvoiceDetailsRelationManager;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\InventoryTransaction;
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
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
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
                    Grid::make()->columns(6)->schema([
                        TextInput::make('invoice_no')
                            ->label(__('lang.invoice_no'))
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->default(fn(): int => (PurchaseInvoice::query()
                                ->orderBy('id', 'desc')
                                ->value('id') + 1 ?? 1))
                            ->placeholder('Enter invoice number')
                            ->disabledOn('edit'),
                        DatePicker::make('date')
                            ->required()
                            ->placeholder('Select date')
                            ->default(date('Y-m-d'))
                            ->format('Y-m-d')
                            ->disabledOn('edit')
                            ->format('Y-m-d'),
                        Select::make('supplier_id')->label(__('lang.supplier'))
                            ->getSearchResultsUsing(fn(string $search): array => Supplier::where('name', 'like', "%{$search}%")->limit(10)->pluck('name', 'id')->toArray())
                            ->getOptionLabelUsing(fn($value): ?string => Supplier::find($value)?->name)
                            ->searchable()
                            ->options(Supplier::limit(5)->get(['id', 'name'])->pluck('name', 'id'))
                            ->disabledOn('edit'),

                        Select::make('store_id')->label(__('lang.store'))
                            ->searchable()
                            ->disabledOn('edit')
                            ->default(getDefaultStore())
                            ->options(
                                Store::where('active', 1)->get(['id', 'name'])->pluck('name', 'id')
                            )
                            ->disabledOn('edit')
                            ->searchable(),
                        Toggle::make('has_attachment')
                            ->label('Has Attachment')
                            ->inline(false)->live(),
                        Toggle::make('has_description')
                            ->label('Has Description')->inline(false)->live(),

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
                    Repeater::make('units')->hiddenOn(['view','edit'])
                        ->createItemButtonLabel(__('lang.add_item'))
                        ->columns(8)
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
                                ->disabledOn('edit')
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
                                ->searchable()->columnSpan(2)
                                ->required(),
                            Select::make('unit_id')
                                ->label(__('lang.unit'))
                                ->disabledOn('edit')
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
                                ->afterStateUpdated(function (\Filament\Forms\Set $set, $state, $get) {
                                    $unitPrice = UnitPrice::where(
                                        'product_id',
                                        $get('product_id')
                                    )->where('unit_id', $state)->first();
                                    $set('price', $unitPrice->price);

                                    $set('total_price', ((float) $unitPrice->price) * ((float) $get('quantity')));
                                    $set('package_size',  $unitPrice->package_size ?? 0);
                                })->columnSpan(2)->required(),
                            TextInput::make('package_size')->type('number')->readOnly()->columnSpan(1)
                                ->label(__('lang.package_size')),
                            TextInput::make('quantity')
                                ->label(__('lang.quantity'))
                                ->type('text')
                                ->minValue(1)
                                ->default(1)
                                ->disabledOn('edit')
                                // ->mask(
                                //     fn (TextInput\Mask $mask) => $mask
                                //         ->numeric()
                                //         ->decimalPlaces(2)
                                //         ->thousandsSeparator(',')
                                // )
                                ->reactive()
                                ->afterStateUpdated(function (\Filament\Forms\Set $set, $state, $get) {

                                    $set('total_price', ((float) $state) * ((float)$get('price') ?? 0));
                                })->columnSpan(1)->required(),
                            TextInput::make('price')
                                ->label(__('lang.price'))
                                ->type('text')
                                ->minValue(1)
                                // ->integer()
                                ->disabledOn('edit')
                                // ->mask(
                                //     fn (TextInput\Mask $mask) => $mask
                                //         ->numeric()
                                //         ->decimalPlaces(2)
                                //         ->thousandsSeparator(',')
                                // )
                                ->reactive()

                                ->afterStateUpdated(function (\Filament\Forms\Set $set, $state, $get) {
                                    $set('total_price', ((float) $state) * ((float)$get('quantity')));
                                })->columnSpan(1)->required(),
                            TextInput::make('total_price')->minValue(1)->label('Total Price')
                                ->type('text')
                                ->extraInputAttributes(['readonly' => true])->columnSpan(1),

                        ])
                ])
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->striped()
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('invoice_no')
                    ->color('primary')
                    ->weight(FontWeight::Bold)->alignCenter(true)
                    ->searchable()->sortable()->toggleable(),
                TextColumn::make('supplier.name')->label('Supplier')->toggleable()->default('-'),
                TextColumn::make('store.name')->label('Store')->toggleable(),
                TextColumn::make('date')->sortable()->toggleable(),
                TextColumn::make('description')->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('details_count')->searchable()->alignCenter(true)
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('has_attachment')->alignCenter(true)->label(__('lang.has_attachment'))
                    ->boolean()->toggleable()
                // ->trueIcon('heroicon-o-badge-check')
                // ->falseIcon('heroicon-o-x-circle')
                ,

            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()
            ])
            ->actions([
                Tables\Actions\Action::make('cancel')
                    ->label('Cancel')->hidden(fn($record): bool => $record->cancelled)
                    ->icon('heroicon-o-backspace')->button()->color(Color::Red)
                    ->form([
                        Textarea::make('cancel_reason')->required()->label('Cancel Reason')
                    ])
                    ->action(function ($record, $data) {
                        $result = $record->cancelInvoice($data['cancel_reason']);

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
                    }),
                Tables\Actions\ActionGroup::make([

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
                        ->color('green')
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
            PurchaseInvoiceDetailsRelationManager::class,
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
}
