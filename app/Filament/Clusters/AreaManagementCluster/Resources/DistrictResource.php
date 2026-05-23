<?php

namespace App\Filament\Clusters\AreaManagementCluster\Resources;

use App\Filament\Clusters\AreaManagementCluster;
use App\Filament\Clusters\AreaManagementCluster\Resources\DistrictResource\Pages\CreateDistrict;
use App\Filament\Clusters\AreaManagementCluster\Resources\DistrictResource\Pages\EditDistrict;
use App\Filament\Clusters\AreaManagementCluster\Resources\DistrictResource\Pages\ListDistricts;
use App\Models\City;
use App\Models\District;
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

class DistrictResource extends Resource
{
    protected static ?string $model = District::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $cluster = AreaManagementCluster::class;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Fieldset::make()->columnSpanFull()->label('')->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),

                    Select::make('city_id')
                        ->label('City')
                        ->options(City::all()->pluck('name', 'id'))
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
                TextColumn::make('city.name')
                    ->label('City')
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
            'index' => ListDistricts::route('/'),
            'create' => CreateDistrict::route('/create'),
            'edit' => EditDistrict::route('/{record}/edit'),
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
