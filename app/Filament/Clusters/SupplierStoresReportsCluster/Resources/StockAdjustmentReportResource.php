<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources;

use App\Filament\Clusters\InventoryManagementCluster;
use App\Filament\Clusters\InventoryReportCluster;
use App\Filament\Clusters\SupplierStoresReportsCluster;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockAdjustmentReportResource\Pages;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockAdjustmentReportResource\RelationManagers;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentDetail;
use App\Models\StockAdjustmentReport;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StockAdjustmentReportResource extends Resource
{
    protected static ?string $model = StockAdjustmentDetail::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = InventoryReportCluster::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 9;

    public static function getPluralLabel(): ?string
    {
        return 'Stock Adjustment Report';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Stock Adjustment Report';
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table->striped()
            ->columns([
                TextColumn::make('product.code'),
                TextColumn::make('product.name'),
                TextColumn::make('unit.name'),
                TextColumn::make('package_size')->alignCenter(true),
                TextColumn::make('quantity')->alignCenter(true),
                TextColumn::make('adjustment_type')->alignCenter(true),
                TextColumn::make('notes'),
            ])
            ->filters([
                //
            ])
            ->actions([])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ])
        ;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = StockAdjustmentDetail::query()
            ->select(
                'product_id',
                'unit_id',
                'package_size',
                'quantity',
                'adjustment_type',
                'notes',
            )->orderBy('id', 'desc');
        return $query;
    }



    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockAdjustmentReports::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
    public static function canDeleteAny(): bool
    {
        if (isSuperAdmin()) {
            return true;
        }
        return false;
    }
}
