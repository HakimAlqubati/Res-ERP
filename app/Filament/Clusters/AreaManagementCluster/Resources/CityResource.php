<?php

namespace App\Filament\Clusters\AreaManagementCluster\Resources;

use App\Filament\Clusters\AreaManagementCluster;
use App\Filament\Clusters\AreaManagementCluster\Resources\CityResource\Pages\CreateCity;
use App\Filament\Clusters\AreaManagementCluster\Resources\CityResource\Pages\EditCity;
use App\Filament\Clusters\AreaManagementCluster\Resources\CityResource\Pages\ListCities;
use App\Models\City;
use App\Models\Country;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CityResource extends Resource
{
    protected static ?string $model = City::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $cluster = AreaManagementCluster::class;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Fieldset::make()->columnSpanFull()->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),

                    Select::make('country_id')
                        ->label('Country')
                        ->options(Country::all()->pluck('name', 'id'))
                        ->searchable()
                        ->required(),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table->striped()
            ->columns([
                TextColumn::make('name')->sortable()->searchable(),
                TextColumn::make('country.name')
                    ->label('Country')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
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
            'index' => ListCities::route('/'),
            'create' => CreateCity::route('/create'),
            'edit' => EditCity::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::query()->count();
    }

    public static function canViewAny(): bool
    {
        if (isSuperAdmin() || isSystemManager() || isBranchManager() || isStoreManager() || isSuperVisor()) {
            return true;
        }

        return false;
    }
}
