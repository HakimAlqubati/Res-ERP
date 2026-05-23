<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources;

use App\Filament\Clusters\InventoryReportCluster;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockAdjustmentReportResource\Pages\ListStockAdjustmentReports;
use App\Models\StockAdjustmentDetail;
use App\Models\Store;
use Filament\Actions\BulkActionGroup;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockAdjustmentReportResource extends Resource
{
    protected static ?string $model = StockAdjustmentDetail::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = InventoryReportCluster::class;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 9;

    protected static bool $shouldRegisterNavigation = false;

    public static function getPluralLabel(): ?string
    {
        return 'Stock Adjustment';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Stock Adjustment';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table->striped()->deferFilters(false)
            ->columns([
                TextColumn::make('id')->searchable()->label('ID')->toggleable()->sortable(),
                TextColumn::make('product.code')->searchable()->label('Code')->toggleable()->sortable(),
                TextColumn::make('product.name')->searchable()->toggleable(),
                TextColumn::make('unit.name')->searchable()->toggleable(),
                TextColumn::make('package_size')->alignCenter(true)->toggleable(),
                TextColumn::make('quantity')->alignCenter(true)
                    ->summarize(Sum::make()),
                TextColumn::make('adjustment_type')->alignCenter(true),
                TextColumn::make('store.name')->toggleable(),
                TextColumn::make('notes'),
                TextColumn::make('createdBy.name')->label('Responsible')->searchable()->toggleable(),
                TextColumn::make('adjustment_date')->label('Date')->searchable()->toggleable()->sortable(),

            ])
            ->filters([
                SelectFilter::make('product.category_id')
                    ->label('Category')
                    ->relationship('product.category', 'name')
                    ->searchable()->preload()
                    ->multiple(),
                SelectFilter::make('proudct_id')
                    ->label('Product')
                    ->relationship('product', 'name', fn ($query) => $query->select('id', 'name', 'code')->limit(10))
                    ->searchable(['name', 'code'])
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->code} - {$record->name}")

                    ->multiple(),
                SelectFilter::make('store_id')->placeholder('Select Store')
                    ->label(__('lang.store'))->searchable()
                    ->options(
                        Store::active()->get()->pluck('name', 'id')->toArray()
                    ),
            ], FiltersLayout::AboveContent)
            ->recordActions([])
            ->toolbarActions([
                BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
                    // Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = StockAdjustmentDetail::query()
            ->select(
                'id',
                'product_id',
                'unit_id',
                'package_size',
                'quantity',
                'adjustment_type',
                'notes',
                'created_by',
                'adjustment_date',
                'store_id',
            )->orderBy('id', 'desc');

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStockAdjustmentReports::route('/'),
        ];
    }

    // public static function getNavigationBadge(): ?string
    // {
    //     return static::getModel()::count();
    // }
    public static function canDeleteAny(): bool
    {
        if (isSuperAdmin()) {
            return true;
        }

        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        return 'Report';
    }

    public static function canViewAny(): bool
    {
        if (isSuperAdmin() || isSystemManager() || isBranchManager() || isStoreManager()) {
            return true;
        }

        return false;
    }
}
