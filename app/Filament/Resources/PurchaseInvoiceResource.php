<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\InventoryCluster;
use App\Filament\Clusters\SupplierCluster;
use App\Filament\Resources\PurchaseInvoiceResource\Pages;
use App\Filament\Resources\PurchaseInvoiceResource\RelationManagers;
use App\Filament\Resources\PurchaseInvoiceResource\RelationManagers\PurchaseInvoiceDetailsRelationManager;
use App\Http\Resources\ProductResource;
use App\Models\Category;
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
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Actions\ActionGroup;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
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
                TextInput::make('invoice_no')->label(__('lang.invoice_no'))
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->placeholder('Enter invoice number')
                // ->disabledOn('edit')
                ,
                DatePicker::make('date')
                    ->required()
                    ->placeholder('Select date')
                    ->default(date('Y-m-d'))
                    ->format('Y-m-d')
                    // ->disabledOn('edit')
                    ->format('Y-m-d'),
                Select::make('supplier_id')->label(__('lang.supplier'))
                    ->getSearchResultsUsing(fn (string $search): array => Supplier::where('name', 'like', "%{$search}%")->limit(10)->pluck('name', 'id')->toArray())
                    ->getOptionLabelUsing(fn ($value): ?string => Supplier::find($value)?->name)
                    ->searchable()
                    ->options(Supplier::limit(5)->get(['id', 'name'])->pluck('name', 'id'))
                    // ->disabledOn('edit')
,
                
                Select::make('store_id')->label(__('lang.store'))
                    ->searchable()
                    ->default(getDefaultStore())
                    ->options(
                        Store::where('active', 1)->get(['id', 'name'])->pluck('name', 'id')
                    )
                    ->disabledOn('edit')
                    ->searchable(),
                Textarea::make('description')->label(__('lang.description'))
                    ->placeholder('Enter description')
                    ->columnSpanFull(),
                FileUpload::make('attachment')
                    ->label(__('lang.attachment'))
                    // ->enableOpen()
                    // ->enableDownload()
                    ->directory('purchase-invoices')
                    ->columnSpanFull()
                    ->acceptedFileTypes(['application/pdf'])
                    ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                        return (string) str($file->getClientOriginalName())->prepend('purchase-invoice-');
                    }),
                Repeater::make('units')
                    ->createItemButtonLabel(__('lang.add_item'))
                    ->columns(5)
                    ->defaultItems(0)
                    ->hiddenOn([
                        // Pages\EditPurchaseInvoice::class,
                        Pages\ViewPurchaseInvoice::class
                    ])
                    ->columnSpanFull()
                    ->collapsible()
                    ->relationship('purchaseInvoiceDetails')
                    ->label(__('lang.purchase_invoice_details'))
                    ->schema([
                        Select::make('product_id')
                            ->label(__('lang.product'))
                            ->searchable()
                            // ->disabledOn('edit')
                            ->options(function () {
                                return Product::limit(10)->pluck('name', 'id');
                            })
                            ->getSearchResultsUsing(fn (string $search): array => Product::where('active',1)->where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id')->toArray())
                            ->getOptionLabelUsing(fn ($value): ?string => Product::find($value)?->name)
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set) => $set('unit_id', null))
                            ->searchable(),
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
                            ->afterStateUpdated(function (\Filament\Forms\Set $set, $state, $get) {
                                $unitPrice = UnitPrice::where(
                                    'product_id',
                                    $get('product_id')
                                )->where('unit_id', $state)->first()->price;
                                $set('price', $unitPrice);

                                $set('total_price', ((float) $unitPrice) * ((float) $get('quantity')));
                            }),
                        TextInput::make('quantity')
                            ->label(__('lang.quantity'))
                            ->type('text')
                            ->default(1)
                            // ->disabledOn('edit')
                            // ->mask(
                            //     fn (TextInput\Mask $mask) => $mask
                            //         ->numeric()
                            //         ->decimalPlaces(2)
                            //         ->thousandsSeparator(',')
                            // )
                            ->reactive()
                            ->afterStateUpdated(function (\Filament\Forms\Set $set, $state, $get) {
                                $set('total_price', ((float) $state) * ((float)$get('price')));
                            }),
                        TextInput::make('price')
                            ->label(__('lang.price'))
                            ->type('text')
                            ->default(1)
                            ->integer()
                            // ->disabledOn('edit')
                            // ->mask(
                            //     fn (TextInput\Mask $mask) => $mask
                            //         ->numeric()
                            //         ->decimalPlaces(2)
                            //         ->thousandsSeparator(',')
                            // )
                            ->reactive()

                            ->afterStateUpdated(function (\Filament\Forms\Set $set, $state, $get) {
                                $set('total_price', ((float) $state) * ((float)$get('quantity')));
                            }),
                        TextInput::make('total_price')->default(1)
                            ->type('text')
                            ->extraInputAttributes(['readonly' => true]),

                    ])
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_no')->searchable()->sortable(),
                TextColumn::make('supplier.name')->label('Supplier'),
                TextColumn::make('store.name')->label('Store'),
                TextColumn::make('date')->sortable(),
                TextColumn::make('description')->searchable(),
                IconColumn::make('has_attachment')->label(__('lang.has_attachment'))
                    ->boolean()
                    // ->trueIcon('heroicon-o-badge-check')
                    // ->falseIcon('heroicon-o-x-circle')
                    ,

            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()
            ])
            ->actions([
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
                        })->hidden(fn ($record) => !(strlen($record['attachment']) > 0))
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


    public static function canDeleteAny(): bool
    {
        return static::can('deleteAny');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
