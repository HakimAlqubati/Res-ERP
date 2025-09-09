<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Fieldset;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockAdjustmentReasonResource\Pages\ListStockAdjustmentReasons;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockAdjustmentReasonResource\Pages\CreateStockAdjustmentReason;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockAdjustmentReasonResource\Pages\EditStockAdjustmentReason;
use App\Filament\Clusters\InventoryManagementCluster;
use App\Filament\Clusters\InventorySettingsCluster;
use App\Filament\Clusters\SupplierStoresReportsCluster;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockAdjustmentReasonResource\Pages;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockAdjustmentReasonResource\RelationManagers;
use App\Models\StockAdjustmentReason;
use Filament\Forms;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StockAdjustmentReasonResource extends Resource
{
    protected static ?string $model = StockAdjustmentReason::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = InventorySettingsCluster::class;
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 1;
    public static function getModelLabel(): string
    {
        return 'Stock Adjustment Reason';
    }
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Fieldset::make()->columnSpanFull()->columns(2)->schema([
                    TextInput::make('name')
                        ->label('Reason Name')
                        ->required()
                        ->maxLength(255),
                    Toggle::make('active')
                        ->inline(false)
                        ->label('Active')
                        ->default(true),

                    Textarea::make('description')->columnSpanFull()
                        ->label('Description')
                        ->nullable()
                        ->maxLength(500),
                ])

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table->striped()->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('name')->label('Reason Name')->searchable()->sortable(),
                TextColumn::make('description')->label('Description')->limit(50),
                ToggleColumn::make('active')->label('Active'),
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
            'index' => ListStockAdjustmentReasons::route('/'),
            'create' => CreateStockAdjustmentReason::route('/create'),
            'edit' => EditStockAdjustmentReason::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
