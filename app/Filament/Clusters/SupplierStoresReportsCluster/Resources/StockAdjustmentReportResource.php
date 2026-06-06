<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources;

use App\Filament\Clusters\InventoryReportCluster;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockAdjustmentReportResource\Pages\ListStockAdjustmentReports;
use App\Models\Product;
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
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
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
                SelectFilter::make('product_id')
                    ->label('Product')
                    ->searchable()
                    ->getSearchResultsUsing(function (string $search): array {
                        return Product::where(function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                              ->orWhere('code', 'like', "%{$search}%");
                        })
                        ->limit(20)
                        ->get()
                        ->mapWithKeys(fn ($p) => [$p->id => "{$p->code} — {$p->name}"])
                        ->toArray();
                    })
                    ->getOptionLabelUsing(
                        fn ($value) => optional(Product::find($value))
                            ->code . ' — ' . optional(Product::find($value))->name
                    )
                    ->multiple(),
                SelectFilter::make('store_id')->placeholder('Select Store')
                    ->label(__('lang.store'))->searchable()
                    ->options(
                        Store::active()->get()->pluck('name', 'id')->toArray()
                    ),
                Filter::make('adjustment_date_range')
                    ->label('Adjustment Date')
                    ->columnSpan(2)
                    ->schema([
                        DatePicker::make('from')->label('From Date'),
                        DatePicker::make('to')->label('To Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('adjustment_date', '>=', $date))
                            ->when($data['to'],   fn ($q, $date) => $q->whereDate('adjustment_date', '<=', $date));
                    }),
                SelectFilter::make('source_type')
                    ->label('Source Type')
                    ->placeholder('All Sources')
                    ->options([
                        'App\Models\StockInventory' => 'Stock Inventory (Stocktake)',
                        'manual'                    => 'Manual Adjustment',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'] ?? null,
                            function ($q, $value) {
                                if ($value === 'manual') {
                                    return $q->whereNull('source_type')
                                             ->orWhere('source_type', '');
                                }
                                return $q->where('source_type', $value);
                            }
                        );
                    }),
                Filter::make('source_id')
                    ->label('Source ID')
                    ->schema([
                        TextInput::make('source_id')
                            ->label('Source ID')
                            ->numeric()
                            ->placeholder('e.g. 12'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['source_id'] ?? null,
                            fn ($q, $id) => $q->where('source_id', (int) $id)
                        );
                    }),
            ], FiltersLayout::Modal)
            ->deferFilters(true)
            ->filtersFormColumns(4)
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
