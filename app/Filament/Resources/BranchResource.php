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
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

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
                                    ->options(User::all()->pluck('name', 'id'))
                                    ->searchable(),
                                Checkbox::make('active')->label(__('lang.active')),
                                Checkbox::make('is_hq')->label(__('lang.is_hq')),
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
                ])->columnSpanFull()->skippable(),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label(__('lang.branch_id')),
                TextColumn::make('name')->label(__('lang.name'))->searchable(),
                TextColumn::make('address')->label(__('lang.address'))
                    // ->limit(100)
                    ->words(5),
                TextColumn::make('user.name')->label(__('lang.branch_manager')),
                TextColumn::make('total_quantity')->label(__('lang.quantity'))
                    ->action(function ($record) {
                        redirect('admin/branch-store-report?tableFilters[branch_id][value]=' . $record->id);
                    }),

            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
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
                Tables\Actions\RestoreBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageBranches::route('/'),
            'edit' => Pages\EditBranch::route('/{record}/edit'),

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
