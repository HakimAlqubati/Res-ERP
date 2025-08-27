<?php

namespace App\Filament\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use App\Models\Store;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use App\Filament\Resources\ManufacturingBranchResource\Pages\ListManufacturingBranches;
use App\Filament\Resources\ManufacturingBranchResource\Pages\CreateManufacturingBranch;
use App\Filament\Resources\ManufacturingBranchResource\Pages\EditManufacturingBranch;
use App\Filament\Clusters\ManufacturingBranchesCluster;
use App\Filament\Resources\ManufacturingBranchResource\Pages;
use App\Models\Branch;
use App\Models\City;
use App\Models\Country;
use App\Models\District;
use App\Models\User; 
use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ManufacturingBranchResource extends Resource
{
    protected static ?string $model = Branch::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster                             = ManufacturingBranchesCluster::class;
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort                         = 0;
    public static function getPluralLabel(): ?string
    {
        return __('menu.manufacturing_branches');
    }
    public static function getPluralModelLabel(): string
    {
        return __('menu.manufacturing_branches');
    }

    public static function getLabel(): ?string
    {
        return __('lang.manufacturing_branch');
    }
    public static function getModelLabel(): string
    {
        return __('lang.manufacturing_branch');
    }
    public static function getNavigationLabel(): string
    {
        return __('menu.manufacturing_branches');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([

                Wizard::make([
                    Step::make('Basic data')
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

                                Grid::make()->columnSpanFull()->columns(3)->schema([
                                    Toggle::make('manager_abel_show_orders')
                                        ->label(__('stock.manager_abel_show_orders'))
                                        ->inline(false)
                                        ->default(false),

                                    Select::make('store_id')
                                        ->label(__('stock.store_id'))
                                        ->options(Store::active()
                                            ->centralKitchen()->pluck('name', 'id'))
                                        ->searchable(),
                                    Select::make('categories')
                                        ->label(__('stock.customized_manufacturing_categories'))
                                        // ->options(\App\Models\Category::Manufacturing()->pluck('name', 'id'))
                                        ->relationship('categories', 'name')

                                        ->searchable()->multiple(),

                                ]),
                                Textarea::make('address')
                                    ->columnSpanFull()
                                    ->label(__('lang.address')),
                            ]),

                        ]),
                    Step::make('Location')
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
                                ]),

                        ]),
                    Step::make('Images')
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
                TrashedFilter::make(),
                SelectFilter::make('active')
                    ->options([
                        1 => __('lang.active'),
                        0 => __('lang.status_unactive'),
                    ])->default(1),

            ])
            ->recordActions([

                Action::make('add_area')
                    ->modalHeading('')
                    ->modalWidth('lg') // Adjust modal size
                    ->button()
                    ->icon('heroicon-o-plus')
                    ->label('Add area')->schema([
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
                                $branch        = $record;                                // Get the branch being updated
                                $existingAreas = $branch->areas->pluck('id')->toArray(); // Existing area IDs

                                foreach ($state as $areaData) {
                                    if (! isset($areaData['id'])) {
                                        // If it's a new area, create it
                                        $branch->areas()->create($areaData);
                                    } else {
                                    }
                                }
                            }),
                    ]),
                Action::make('quick_edit')
                    ->label(__('Quick Edit'))
                    ->icon('heroicon-o-pencil-square')
                    ->modalHeading(__('Quick Edit Branch'))
                    ->modalWidth('lg')
                    ->schema(function ($record) {
                        return [
                            TextInput::make('name')->required()->label(__('lang.name'))->default($record->name),
                            Select::make('manager_id')
                                ->label(__('lang.branch_manager'))->default($record->manager_id)
                                ->options(User::whereHas('roles', fn($q) => $q->where('id', 7))
                                    ->pluck('name', 'id')),
                            Select::make('store_id')
                                ->label(__('stock.store_id'))->default($record->store_id)
                                ->options(Store::active()->centralKitchen()->pluck('name', 'id'))
                                ->searchable(),

                        ];
                    })
                    ->action(function (Model $record, array $data) {
                        $record->update($data);
                        Notification::make()
                            ->title(__('Updated successfully'))
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
                ForceDeleteBulkAction::make(),
                RestoreBulkAction::make(),
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
            'index'  => ListManufacturingBranches::route('/'),
            'create' => CreateManufacturingBranch::route('/create'),
            'edit'   => EditManufacturingBranch::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): EloquentBuilder
    {
        $query = static::getModel()::query()->where('type', Branch::TYPE_CENTRAL_KITCHEN);

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
