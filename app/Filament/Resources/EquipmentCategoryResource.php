<?php

namespace App\Filament\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Fieldset;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\EquipmentCategoryResource\Pages\ListEquipmentCategories;
use App\Filament\Resources\EquipmentCategoryResource\Pages\CreateEquipmentCategory;
use App\Filament\Resources\EquipmentCategoryResource\Pages\EditEquipmentCategory;
use App\Filament\Clusters\HRServiceRequestCluster;
use App\Filament\Resources\EquipmentCategoryResource\Pages;
use App\Filament\Resources\EquipmentCategoryResource\RelationManagers;
use App\Models\EquipmentCategory;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EquipmentCategoryResource extends Resource
{
    protected static ?string $model = EquipmentCategory::class;

    protected static ?string $cluster = HRServiceRequestCluster::class;
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 4;

    protected static ?string $label = 'Equipment Category';
    protected static ?string $pluralLabel = 'Equipment Categories';
    protected static string | \BackedEnum | null $navigationIcon = Heroicon::Folder;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Fieldset::make()->columnSpanFull()->columns(3)->schema([
                    TextInput::make('name')
                        ->label('Name')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('equipment_code_start_with')
                        ->label('Code Prefix')->required()
                        ->helperText('Prefix used for equipment code generation.')
                        ->maxLength(20),
                    Toggle::make('active')
                        ->label('Active')->inline(false)
                        ->default(true),

                    Textarea::make('description')
                        ->label('Description')
                        ->rows(3),
                ])

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table->striped()->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')->label('ID')->sortable()->searchable()->toggleable()->alignCenter(true),
                TextColumn::make('name')->label('Name')->sortable()->searchable()->toggleable(),
                TextColumn::make('equipment_code_start_with')->label('Code Prefix')->sortable()->toggleable()->alignCenter(true),
                TextColumn::make('description')->label('Description')->limit(50)->toggleable()->alignCenter(true),
                IconColumn::make('active')->label('Active')->boolean()->toggleable()->alignCenter(true),
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
            'index' => ListEquipmentCategories::route('/'),
            'create' => CreateEquipmentCategory::route('/create'),
            'edit' => EditEquipmentCategory::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return self::getModel()::count();
    }
    public static function canViewAny(): bool
    {
        if (isSuperAdmin() || isSystemManager()  || isMaintenanceManager()) {
            return true;
        }
        return false;
    }
}
