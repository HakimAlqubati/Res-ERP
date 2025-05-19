<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BranchResource\Pages;
use App\Filament\Resources\BranchResource\RelationManagers\AreasRelationManager;
use App\Models\Branch;
use App\Models\City;
use App\Models\Country;
use App\Models\District;
use App\Models\User;
use ArberMustafa\FilamentLocationPickrField\Forms\Components\LocationPickr;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class BranchResource extends Resource
{
    protected static ?string $model = Branch::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationGroup = 'Branches';
    public static function getNavigationLabel(): string
    {
        return __('lang.branches');
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Wizard::make([
                    Wizard\Step::make('Basic data')
                        ->icon('heroicon-o-user-circle')
                        ->schema([
                            Fieldset::make()->columns(2)->schema([
                                TextInput::make('name')->required()->label(__('lang.name')),
                                Select::make('manager_id')
                                    ->label(__('lang.branch_manager'))
                                    ->options(User::whereHas('roles', function ($q) {
                                        $q->where('id', 7);
                                    })
                                        ->get(['name', 'id'])->pluck('name', 'id'))
                                    ->searchable(),
                                Grid::make()->columns(4)->schema([

                                    //     ->label(__('lang.active')),
                                    // Select::make('type')
                                    //     ->label(__('lang.branch_type'))
                                    //     ->required()
                                    //     ->default(function () {
                                    //         // 🧠 التقاط قيمة type من URL تلقائياً (activeTab)
                                    //         $tab = request()->get('activeTab');
                                    //         return in_array($tab, Branch::TYPES) ? $tab : Branch::TYPE_BRANCH;
                                    //     })
                                    //     ->options([
                                    //         Branch::TYPE_BRANCH => __('lang.normal_branch'),
                                    //         Branch::TYPE_CENTRAL_KITCHEN => __('lang.central_kitchen'),
                                    //         Branch::TYPE_HQ => __('lang.hq'),
                                    //         Branch::TYPE_POPUP => __('lang.popup_branch'),
                                    //     ])
                                    //     ->default(Branch::TYPE_BRANCH)

                                    //     ->reactive(),
                                    ToggleButtons::make('type')->required()
                                        ->default(function () {
                                            $tab = request()->get('activeTab');
                                            return in_array($tab, Branch::TYPES) ? $tab : Branch::TYPE_BRANCH;
                                        })->options([
                                            Branch::TYPE_BRANCH => __('lang.normal_branch'),
                                            Branch::TYPE_CENTRAL_KITCHEN => __('lang.central_kitchen'),
                                            Branch::TYPE_HQ => __('lang.hq'),
                                            Branch::TYPE_POPUP => __('lang.popup_branch'),
                                        ])->columns(4)
                                        ->default(Branch::TYPE_BRANCH)
                                        ->icons([
                                            Branch::TYPE_BRANCH => 'heroicon-o-building-storefront',
                                            Branch::TYPE_CENTRAL_KITCHEN => 'heroicon-o-fire',
                                            Branch::TYPE_HQ => 'heroicon-o-building-storefront',
                                            Branch::TYPE_POPUP => 'heroicon-o-sparkles',
                                        ])
                                        ->colors([
                                            Branch::TYPE_BRANCH => 'warning',
                                            Branch::TYPE_CENTRAL_KITCHEN => 'info',
                                            Branch::TYPE_HQ => 'success',
                                            Branch::TYPE_POPUP => 'danger',
                                        ])
                                        ->inline()
                                        ->reactive()->columnSpan(3),
                                    Toggle::make('active')
                                        ->inline(false)->default(true),
                                    Grid::make()->columnSpanFull()->columns(3)->schema([
                                        Toggle::make('manager_abel_show_orders')
                                            ->label(__('stock.manager_abel_show_orders'))
                                            ->inline(false)
                                            ->default(false)
                                            ->visible(fn(callable $get) => $get('type') === Branch::TYPE_CENTRAL_KITCHEN),

                                        Select::make('store_id')
                                            ->label(__('stock.store_id'))
                                            ->options(\App\Models\Store::active()->centralKitchen()->pluck('name', 'id'))
                                            ->searchable()
                                            ->requiredIf('type', Branch::TYPE_CENTRAL_KITCHEN)
                                        // ->visible(fn(callable $get) => $get('type') === Branch::TYPE_CENTRAL_KITCHEN)
                                        ,
                                        Select::make('categories')
                                            ->label(__('stock.customized_manufacturing_categories'))
                                            // ->options(\App\Models\Category::Manufacturing()->pluck('name', 'id'))
                                            ->relationship('categories', 'name')

                                            ->searchable()->multiple()
                                            ->visible(fn(callable $get) => $get('type') === Branch::TYPE_CENTRAL_KITCHEN),

                                    ]),

                                ]),
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
                                    ->circleCropper()
                            ])
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
                TextColumn::make('type_title')->label(__('lang.branch_type')),
                IconColumn::make('active')->boolean()->label(__('lang.active'))->alignCenter(true),
                TextColumn::make('address')->label(__('lang.address'))
                    // ->limit(100)
                    ->words(5)->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('user.name')->label(__('lang.branch_manager')),
                TextColumn::make('category_names')->label(__('stock.customized_manufacturing_categories'))->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('user.email')->label('Email')->copyable(),
                TextColumn::make('total_quantity')->label(__('lang.quantity'))
                    ->action(function ($record) {
                        redirect('admin/branch-store-report?tableFilters[branch_id][value]=' . $record->id);
                    })->hidden(),
                TextColumn::make('start_date')
                    ->label(__('lang.start_date'))
                    ->dateTime('Y-m-d')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('end_date')
                    ->label(__('lang.end_date'))
                    ->dateTime('Y-m-d')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('orders_count')
                    ->formatStateUsing(fn($record): string => $record?->orders()?->count() ?? 0)
                    ->label(__('lang.orders'))->alignCenter(true)->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('store.name')

                    ->label(__('lang.store'))->alignCenter(true)->toggleable(isToggledHiddenByDefault: false),

            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),

                // Tables\Filters\SelectFilter::make('category')
                //     ->label(__('stock.customized_manufacturing_categories'))
                //     ->options(\App\Models\Category::pluck('name', 'id'))
                // ->query(function (Builder $query, $value) {
                //     $query->whereHas('categories', fn($q) => $q->where('id', $value));
                // }),
            ])
            ->actions([
                Action::make('add_area')
                    ->modalHeading('')
                    ->modalWidth('lg') // Adjust modal size
                    ->button()
                    ->icon('heroicon-o-plus')
                    ->label('Add area')->form([
                        Repeater::make('branch_areas')
                            ->minItems(1)
                            ->maxItems(1)
                            ->disableItemCreation(true)
                            ->disableItemDeletion(true)

                            ->schema([
                                TextInput::make('name')->label('Area name')->required()->helperText('Type the name of area'),
                                Textarea::make('description')->label('Description')->helperText('More information about the area, like floor, location ...etc'),
                            ])
                            ->afterStateUpdated(function ($state, $record) {

                                // Custom logic to handle saving without deleting existing records
                                $branch = $record; // Get the branch being updated
                                $existingAreas = $branch->areas->pluck('id')->toArray(); // Existing area IDs

                                foreach ($state as $areaData) {
                                    if (!isset($areaData['id'])) {
                                        // If it's a new area, create it
                                        $branch->areas()->create($areaData);
                                    } else {
                                    }
                                }
                            }),
                    ]),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\ForceDeleteBulkAction::make(),
                Tables\Actions\RestoreBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageBranches::route('/'),
            'edit' => Pages\EditBranch::route('/{record}/edit'),
            'create' => Pages\CreateBranch::route('/create'),


        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getRelations(): array
    {
        return [
            AreasRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
    public static function canViewAny(): bool
    {
        return true;
    }

    public static function canCreate(): bool
    {
        if (isSuperAdmin() || isSystemManager()) {
            return true;
        }
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (isSuperAdmin() || isSystemManager()) {
            return true;
        }
        return false;
    }

    public static function canDeleteAny(): bool
    {
        if (isSuperAdmin() || isSystemManager()) {
            return true;
        }
        return false;
    }
}
