<?php

namespace App\Filament\Clusters\HRCluster\Resources;

use App\Filament\Clusters\HRCluster;
use App\Filament\Clusters\HRCluster\Resources\EmployeeFileTypeResource\Pages;
use App\Filament\Clusters\HRCluster\Resources\EmployeeFileTypeResource\RelationManagers;
use App\Models\EmployeeFileType;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\HasManyRepeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EmployeeFileTypeResource extends Resource
{
    protected static ?string $model = EmployeeFileType::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = HRCluster::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 4;
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make()->schema([
                    Grid::make()->columns(3)->schema([
                        TextInput::make('name')->required()->columnSpan(1),
                        Toggle::make('active')->default(1)->columnSpan(1)->inline(false),
                        Toggle::make('is_required')->label('Is required for employee?')->default(0)->columnSpan(1)->inline(false),

                    ]),
                    Textarea::make('description')->columnSpanFull(),
                ]),
                Fieldset::make('Dynamic Fields')->schema([
                    HasManyRepeater::make('dynamicFields')
                        ->relationship('dynamicFields') // Define the relationship for the dynamic fields
                        ->schema([
                            TextInput::make('field_name')
                                ->label('Field Name')
                                ->required(),
                                Select::make('field_type')
                                ->label('Field Type')
                                ->required()
                                ->options([
                                    'text' => 'Text',
                                    'number' => 'Number',
                                    'date' => 'Date',
                                ])
                                ->placeholder('Select a field type'),
                        ])
                        ->label('Dynamic Fields')
                        ->createItemButtonLabel('Add New Dynamic Field'),
                ])
                ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->searchable()->sortable(),
                TextColumn::make('name')->searchable(),
                TextColumn::make('description')->searchable(),
                ToggleColumn::make('active')->disabled()->searchable()->sortable(),
                ToggleColumn::make('is_required')->disabled()->searchable()->sortable(),
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
            'index' => Pages\ListEmployeeFileTypes::route('/'),
            // 'create' => Pages\CreateEmployeeFileType::route('/create'),
            // 'edit' => Pages\EditEmployeeFileType::route('/{record}/edit'),
        ];
    }
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function canViewAny(): bool
    {
        if (isSuperAdmin() || isSystemManager() || isBranchManager() ) {
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

   
    public static function canForceDelete(Model $record): bool
    {
        return false;
    }

    public static function canForceDeleteAny(): bool
    {
        return false;
    }

}
