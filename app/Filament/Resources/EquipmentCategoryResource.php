<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\HRServiceRequestCluster;
use App\Filament\Resources\EquipmentCategoryResource\Pages;
use App\Filament\Resources\EquipmentCategoryResource\RelationManagers;
use App\Models\EquipmentCategory;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EquipmentCategoryResource extends Resource
{
    protected static ?string $model = EquipmentCategory::class;

    protected static ?string $cluster = HRServiceRequestCluster::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 4;

    protected static ?string $label = 'Equipment Category';
    protected static ?string $pluralLabel = 'Equipment Categories';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make()->columns(3)->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Name')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('equipment_code_start_with')
                        ->label('Code Prefix')->required()
                        ->helperText('Prefix used for equipment code generation.')
                        ->maxLength(20),
                    Forms\Components\Toggle::make('active')
                        ->label('Active')->inline(false)
                        ->default(true),

                    Forms\Components\Textarea::make('description')
                        ->label('Description')
                        ->rows(3),
                ])

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table->striped()->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable()->searchable()->toggleable()->alignCenter(true),
                Tables\Columns\TextColumn::make('name')->label('Name')->sortable()->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('equipment_code_start_with')->label('Code Prefix')->sortable()->toggleable()->alignCenter(true),
                Tables\Columns\TextColumn::make('description')->label('Description')->limit(50)->toggleable()->alignCenter(true),
                Tables\Columns\IconColumn::make('active')->label('Active')->boolean()->toggleable()->alignCenter(true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListEquipmentCategories::route('/'),
            'create' => Pages\CreateEquipmentCategory::route('/create'),
            'edit' => Pages\EditEquipmentCategory::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return self::getModel()::count();
    }
}