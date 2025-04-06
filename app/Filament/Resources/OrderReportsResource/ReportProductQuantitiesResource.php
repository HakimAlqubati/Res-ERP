<?php

namespace App\Filament\Resources\OrderReportsResource;

use App\Filament\Clusters\MainOrdersCluster;
use App\Filament\Clusters\OrderCluster;
use App\Filament\Clusters\ReportOrdersCluster;
use App\Filament\Resources\OrderReportsResource\Pages\ListReportProductQuantities;
use App\Models\Branch;
use App\Models\FakeModelReports\ReportProductQuantities;
use App\Models\OrderDetails;
use App\Models\Product;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class ReportProductQuantitiesResource extends Resource
{
    protected static ?string $model = ReportProductQuantities::class;
    protected static ?string $slug = 'report-product-quantities';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $cluster = MainOrdersCluster::class;
    protected static bool $shouldRegisterNavigation = true;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 5;

    /**
     * @deprecated Use `getModelLabel()` instead.
     */
    public static function getLabel(): ?string
    {
        return __('lang.report_product_quantities');
    }
    public static function getNavigationLabel(): string
    {
        return __('lang.report_product_quantities');
    }

    public static function getPluralLabel(): ?string
    {
        return __('lang.report_product_quantities');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReportProductQuantities::route('/'),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort(null)
            ->emptyStateHeading('Please choose a product')
            ->emptyStateDescription('Please choose a product or maybe there is no data')
            ->emptyStateIcon('heroicon-o-plus')
            ->columns([
                TextColumn::make('product')->limit(25)
                    ->default('You should to select a product'),
                TextColumn::make('branch'),
                TextColumn::make('unit'),
                TextColumn::make('quantity')->alignCenter(true),
                TextColumn::make('price'),
            ])
            ->filters([
                // SelectFilter::make('product_id')
                //     ->label('Product')->searchable()
                //     ->selectablePlaceholder('Should to select product')
                //     ->options(Product::pluck('name', 'id')),
                SelectFilter::make('product_id')
                    ->label('Product')
                    ->searchable()
                    ->multiple()
                    ->placeholder('All products')  // Custom placeholder option
                    ->options(
                        Product::pluck('name', 'id')->toArray()
                    ),
                SelectFilter::make('branch_id')
                    ->label('Branch')->searchable()
                    ->options(Branch::pluck('name', 'id')),

                Filter::make('date_range')
                    ->form([
                        DatePicker::make('start_date')
                            ->label('Start Date'),
                        DatePicker::make('end_date')
                            ->label('End Date'),
                    ])
                    // ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data): \Illuminate\Database\Eloquent\Builder {

                    //     return $query->when(
                    //         isset($data['start_date']) && isset($data['end_date']),
                    //         fn($query) => $query->whereBetween('orders.created_at', [$data['start_date'], $data['end_date']])
                    //     );
                    // })
                    ,
            ], layout: FiltersLayout::AboveContent);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        // return static::getModel()::query()->orderBy('product');
        // // Extract filter values from the request
        $updates = request()->input('components.0.updates', []); 
        // $start_date = $updates['tableFilters.date_range.start_date'] ?? null;
        // $end_date = $updates['tableFilters.date_range.end_date'] ?? null;
        // dd($product_id,$updates);
        // Build the query using Eloquent
        $query = OrderDetails::query()
            ->select(
                'products.name AS product',
                'products.id AS product_id',
                'branches.name AS branch',
                'units.name AS unit',
                DB::raw('SUM(orders_details.available_quantity) AS quantity'),
                DB::raw('SUM(orders_details.available_quantity) * orders_details.price AS price')
            )
            ->join('products', 'orders_details.product_id', '=', 'products.id')
            ->join('orders', 'orders_details.order_id', '=', 'orders.id')
            ->join('branches', 'orders.branch_id', '=', 'branches.id')
            ->join('units', 'orders_details.unit_id', '=', 'units.id')
            ->whereNull('orders.deleted_at')
            // ->where('products.id', $product_id)
            ->groupBy('orders.branch_id', 'products.name', 'products.id', 'branches.name', 'units.name', 'orders_details.price');
        return $query;
    }
}
