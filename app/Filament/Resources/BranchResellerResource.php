<?php
namespace App\Filament\Resources;

use App\Filament\Clusters\ResellersCluster;
use App\Filament\Resources\BranchResellerResource\Pages;
use App\FilamentTables\Actions\ManageStoreAction;
use App\Models\Branch;
use App\Models\City;
use App\Models\Country;
use App\Models\District;
use App\Models\Store;
use App\Models\User;
use ArberMustafa\FilamentLocationPickrField\Forms\Components\LocationPickr;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class BranchResellerResource extends Resource
{
    protected static ?string $model = Branch::class;

    protected static ?string $navigationIcon                      = 'heroicon-o-rectangle-stack';
    protected static ?string $cluster                             = ResellersCluster::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort                         = 0;
    public static function getPluralLabel(): ?string
    {
        return __('menu.resellers');
    }
    public static function getPluralModelLabel(): string
    {
        return __('menu.resellers');
    }

    public static function getLabel(): ?string
    {
        return __('lang.reseller');
    }
    public static function getModelLabel(): string
    {
        return __('lang.reseller');
    }
    public static function getNavigationLabel(): string
    {
        return __('menu.resellers');
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Wizard::make([
                    Wizard\Step::make('Basic data')
                        ->icon('heroicon-o-user-circle')
                        ->schema([
                            Fieldset::make()->columns(3)->schema([
                                TextInput::make('name')->required()->label(__('lang.name')),
                                Select::make('manager_id')
                                    ->label(__('lang.account_manager'))
                                    ->options(User::whereHas('roles', function ($q) {
                                        $q->where('id', 7);
                                    })
                                            ->get(['name', 'id'])->pluck('name', 'id'))
                                    ->searchable(),
                                Toggle::make('active')
                                    ->inline(false)->default(true),

                                Fieldset::make()->columns(2)
                                    ->visible(fn(callable $get) => $get('type') === Branch::TYPE_POPUP)
                                    ->label('Set Start and End Date for Popup Branch')
                                    ->schema([
                                        DateTimePicker::make('start_date')
                                            ->default(now()->addDay())

                                            ->label(__('lang.start_date'))
                                            ->required(fn(callable $get) => $get('type') === Branch::TYPE_POPUP)
                                            ->visible(fn(callable $get) => $get('type') === Branch::TYPE_POPUP)
                                            ->live()
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                if ($state) {
                                                    $newEndDate = \Illuminate\Support\Carbon::parse($state)->addDay();
                                                    $set('end_date', $newEndDate);
                                                }
                                            }),

                                        DateTimePicker::make('end_date')
                                            ->label(__('lang.end_date'))
                                            ->default(now()->addDays(2))

                                            ->required(fn(callable $get) => $get('type') === Branch::TYPE_POPUP)
                                            ->after('start_date')
                                            ->visible(fn(callable $get) => $get('type') === Branch::TYPE_POPUP),
                                        Textarea::make('more_description')
                                            ->label(__('lang.more_description'))
                                            ->rows(3)->columnSpanFull()
                                            ->nullable()
                                            ->visible(fn(callable $get) => $get('type') === Branch::TYPE_POPUP),
                                    ]),
                                Select::make('categories')
                                    ->label(__('lang.customized_categories'))
                                // ->options(\App\Models\Category::Manufacturing()->pluck('name', 'id'))
                                    ->relationship('categories', 'name')
                                    ->columnSpanFull()->preload()
                                    ->searchable()->multiple()->required(),
                                Textarea::make('address')
                                    ->columnSpanFull()
                                    ->label(__('lang.address')),
                            ]),

                        ]),
                    Wizard\Step::make('Location')
                        ->icon('heroicon-o-map-pin')
                        ->schema([
                            Fieldset::make()
                                ->relationship('location')
                                ->columns(3)->schema([
                                Select::make('country_id')
                                    ->label(__('Country'))->searchable()
                                // ->relationship('city', 'name')
                                    ->options(Country::get(['id', 'name'])->pluck('name', 'id'))
                                    ->reactive()
                                    ->required(false),
                                Select::make('city_id')
                                    ->label(__('City'))->searchable()
                                // ->relationship('city', 'name')
                                    ->options(function (callable $get) {
                                        $countryId = $get('country_id');
                                        return $countryId ? City::where('country_id', $countryId)->pluck('name', 'id') : [];
                                    })
                                    ->reactive()
                                    ->required(false),

                                Select::make('district_id')
                                    ->label(__('District'))
                                    ->searchable()
                                    ->options(function (callable $get) {
                                        $cityId = $get('city_id');
                                        return $cityId ? District::where('city_id', $cityId)->pluck('name', 'id') : [];
                                    })
                                    ->reactive()
                                    ->required(false),
                                Textarea::make('address')->label(__('lang.address'))->columnSpanFull(),
                                LocationPickr::make('location')->label('')->columnSpanFull()
                                    ->mapControls([
                                        'mapTypeControl'    => true,
                                        'scaleControl'      => true,
                                        'streetViewControl' => true,
                                        'rotateControl'     => true,
                                        'fullscreenControl' => true,
                                        'zoomControl'       => false,
                                    ])
                                    ->defaultZoom(5)
                                    ->draggable()
                                    ->clickable()
                                    ->height('40vh')
                                // ->defaultLocation([41.32836109345274, 19.818383186960773])
                                    ->myLocationButtonLabel('My location'),
                                // Add other location fields as needed (district_id, city_id, etc.)

                            ]),

                        ]),
                    Wizard\Step::make('Images')
                        ->icon('heroicon-o-user-circle')
                        ->schema([
                            Fieldset::make()->columns(1)->schema([
                                SpatieMediaLibraryFileUpload::make('images')
                                    ->disk('public')
                                    ->label('')
                                    ->directory('branches')
                                    ->columnSpanFull()
                                    ->image()
                                    ->multiple()
                                    ->downloadable()
                                    ->moveFiles()
                                    ->previewable()
                                    ->imagePreviewHeight('250')
                                    ->loadingIndicatorPosition('right')
                                    ->panelLayout('integrated')
                                    ->removeUploadedFileButtonPosition('right')
                                    ->uploadButtonPosition('right')
                                    ->uploadProgressIndicatorPosition('right')
                                    ->panelLayout('grid')
                                    ->reorderable()
                                    ->openable()
                                    ->downloadable(true)
                                    ->previewable(true)
                                    ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                                        return (string) str($file->getClientOriginalName())->prepend('branch-');
                                    })
                                    ->imageEditor()
                                    ->imageEditorAspectRatios([
                                        '16:9',
                                        '4:3',
                                        '1:1',
                                    ])->maxSize(800)
                                    ->imageEditorMode(2)
                                    ->imageEditorEmptyFillColor('#fff000')
                                    ->circleCropper(),
                            ]),
                        ]),
                ])->columnSpanFull()->skippable(),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table->striped()
            ->columns([
                TextColumn::make('id')->label(__('lang.branch_id'))->alignCenter(true)->toggleable(isToggledHiddenByDefault: true),
                SpatieMediaLibraryImageColumn::make('')->label('')->size(50)
                    ->circular()->alignCenter(true)->getStateUsing(function () {
                    return null;
                })->limit(3),
                TextColumn::make('name')->label(__('lang.name'))->searchable(),
                TextColumn::make('store.name')->label(__('lang.store'))->searchable()->toggleable(),
                IconColumn::make('active')->boolean()->label(__('lang.active'))->alignCenter(true),
                TextColumn::make('address')->label(__('lang.address'))
                // ->limit(100)
                    ->words(5)->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('user.name')->label(__('lang.account_manager'))->toggleable(),

                TextColumn::make('user.email')->label('Email')->copyable()->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('orders_count')
                    ->formatStateUsing(fn($record): string => $record?->orders()?->count() ?? 0)
                    ->label(__('lang.delivery_orders'))->alignCenter(true)->toggleable(isToggledHiddenByDefault: false),
                // TextColumn::make('reseller_balance')
                //     ->label('Balance')
                //     ->formatStateUsing(fn($state) => formatMoneyWithCurrency($state))
                //     ->sortable(),

                TextColumn::make('total_orders_amount')
                    ->label('DO Total')
                    ->formatStateUsing(fn($state) => formatMoneyWithCurrency($state))
                    ->sortable(),

                TextColumn::make('total_sales')
                    ->label('Total Sales')->toggleable()
                    ->formatStateUsing(fn($state) => formatMoneyWithCurrency($state))
                    ->sortable(),

                TextColumn::make('total_paid')
                    ->label('Total Paid')->toggleable()
                    ->formatStateUsing(fn($state) => formatMoneyWithCurrency($state))
                    ->sortable(),

            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->options(\App\Models\Branch::resellers()->active()->pluck('name', 'id')),
                Tables\Filters\SelectFilter::make('active')
                    ->options([
                        1 => __('lang.active'),
                        0 => __('lang.status_unactive'),
                    ])->default(1),

            ])
            ->actions([

                Action::make('addStore')
                    ->label('Add Store')
                    ->icon('heroicon-o-plus-circle')
                    ->visible(fn(Model $record) => ! $record->store)
                    ->form([
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
                        } catch (\Throwable $th) {
                            throw $th;
                        }
                    })
                    ->modalHeading('Create and Link Store')
                    ->color('primary')
                    ->button(),
                Action::make('viewInventory')
                    ->label('View Inventory')->button()
                    ->icon('heroicon-o-chart-bar')
                    ->url(fn(Model $record) => \App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryTransactionReportResource::getUrl('index',
                     ['store_id' =>
                        $record->store_id,
                        'only_available' => 1
                        , 'from_url' => 'branch-resellers',
                        ]))
                    ->openUrlInNewTab()
                    ->visible(fn(Model $record) => $record->hasStore()),
             
                    ManageStoreAction::makeForResource(),

                Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
                // Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                // Tables\Actions\DeleteBulkAction::make(),
                // Tables\Actions\ForceDeleteBulkAction::make(),
                // Tables\Actions\RestoreBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // SalesAmountsRelationManager::class,
            // PaidAmountsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListBranchResellers::route('/'),
            'create' => Pages\CreateBranchReseller::route('/create'),
            'view'   => Pages\ViewBranchReseller::route('/{record}'),
            'edit'   => Pages\EditBranchReseller::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = static::getModel()::query()->where('type', Branch::TYPE_RESELLER);

        if (
            static::isScopedToTenant() &&
            ($tenant = Filament::getTenant())
        ) {
            static::scopeEloquentQueryToTenant($query, $tenant);
        }

        return $query;
    }

    public static function getNavigationBadge(): ?string
    {
        return self::getEloquentQuery()->count();
    }

    public static function getNavigationBadgeColor(): string | array | null
    {
        return Color::Red;
    }
}