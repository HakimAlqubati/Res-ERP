<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\HRServiceRequestCluster;
use App\Filament\Resources\EquipmentTypeResource\Pages;
use App\Filament\Resources\EquipmentTypeResource\RelationManagers;
use App\Models\EquipmentType;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EquipmentTypeResource extends Resource
{
    protected static ?string $model = EquipmentType::class;

    protected static ?string $cluster = HRServiceRequestCluster::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 3;

    protected static ?string $label = 'Equipment Type';
    protected static ?string $pluralLabel = 'Equipment Types';

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
                        ->label('Equipment Code Prefix')
                        ->maxLength(50)
                        ->helperText('Optional code prefix to be used when generating equipment codes.')->required(),

                    Forms\Components\Toggle::make('active')
                        ->label('Active')
                        ->default(true),

                    Forms\Components\Textarea::make('description')
                        ->label('Description')
                        ->rows(3)->columnSpanFull(),

                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table->striped()->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->sortable()
                    ->searchable()->toggleable(),

                Tables\Columns\TextColumn::make('equipment_code_start_with')
                    ->label('Code Prefix')
                    ->sortable()->toggleable()
                    ->searchable()->alignCenter(true),

                Tables\Columns\TextColumn::make('description')
                    ->label('Description')->toggleable()
                    ->limit(50),

                Tables\Columns\IconColumn::make('active')
                    ->label('Active')->alignCenter(true)->toggleable()
                    ->boolean(),
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
            'index' => Pages\ListEquipmentTypes::route('/'),
            'create' => Pages\CreateEquipmentType::route('/create'),
            'edit' => Pages\EditEquipmentType::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return self::getModel()::count();
    }
}
