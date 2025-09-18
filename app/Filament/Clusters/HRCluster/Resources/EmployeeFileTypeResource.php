<?php

namespace App\Filament\Clusters\HRCluster\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\RelationshipRepeater;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Clusters\HRCluster\Resources\EmployeeFileTypeResource\Pages\ListEmployeeFileTypes;
use App\Filament\Clusters\HRCluster;
use App\Filament\Clusters\HRCluster\Resources\EmployeeFileTypeResource\Pages;
use App\Filament\Clusters\HRCluster\Resources\EmployeeFileTypeResource\RelationManagers;
use App\Models\EmployeeFileType;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
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

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::DocumentDuplicate;

    protected static ?string $cluster = HRCluster::class;
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 4;
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Fieldset::make()->columnSpanFull()->schema([
                    Grid::make()->columnSpanFull()->columns(3)->schema([
                        TextInput::make('name')->required()->columnSpan(1),
                        Toggle::make('active')->default(1)->columnSpan(1)->inline(false),
                        Toggle::make('is_required')->label('Is required for employee?')->default(0)->columnSpan(1)->inline(false),

                    ]),
                    Textarea::make('description')->columnSpanFull(),
                ]),
                Fieldset::make('Dynamic Fields')->columnSpanFull()->schema([
                    RelationshipRepeater::make('dynamicFields')
                        ->relationship('dynamicFields') // Define the relationship for the dynamic fields
                        ->columnSpanFull()
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
                        ->label('')
                        ->createItemButtonLabel('Add New Dynamic Field'),
                ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table->striped()
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
            'index' => ListEmployeeFileTypes::route('/'),
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


    public static function canForceDelete(Model $record): bool
    {
        return false;
    }

    public static function canForceDeleteAny(): bool
    {
        return false;
    }
}
