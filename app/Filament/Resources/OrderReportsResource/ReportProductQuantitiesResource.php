<?php

namespace App\Filament\Resources\OrderReportsResource;

use App\Filament\Clusters\MainOrdersCluster;
use App\Filament\Clusters\OrderCluster;
use App\Filament\Clusters\OrderReportsCluster;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ReportProductQuantitiesResource extends Resource
{
    protected static ?string $model = ReportProductQuantities::class;
    protected static ?string $slug = 'report-product-quantities';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $cluster = OrderReportsCluster::class;
    protected static bool $shouldRegisterNavigation = true;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 2;

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
                TextColumn::make('code')->alignCenter(true),
                TextColumn::make('product')->limit(25)
                    ->default('You should to select a product'),
                TextColumn::make('branch'),
                TextColumn::make('unit'),
                TextColumn::make('package_size')->alignCenter(true),
                TextColumn::make('quantity')->alignCenter(true),
                TextColumn::make('unit_price')
                    ->hidden(fn(): bool => isStoreManager())
                    ->formatStateUsing(fn($state): string => getDefaultCurrency() . ' ' . $state),
                TextColumn::make('total_price')
                    ->hidden(fn(): bool => isStoreManager())
                    ->formatStateUsing(fn($state): string => getDefaultCurrency() . ' ' . $state),
            ])
            ->filters([
                // SelectFilter::make('product_id')
                //     ->label('Product')->searchable()
                //     ->selectablePlaceholder('Should to select product')
                //     ->options(Product::pluck('name', 'id')),
                SelectFilter::make("product_id")
                    ->multiple()
                    ->label(__('lang.product'))->searchable()
                    ->getSearchResultsUsing(function (string $search): array {
                        return Product::query()
                            ->where(function ($query) use ($search) {
                                $query->where('name', 'like', "%{$search}%")
                                    ->orWhere('code', 'like', "%{$search}%");
                            })
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(fn($product) => [
                                $product->id => "{$product->code} - {$product->name}"
                            ])
                            ->toArray();
                    })
                    ->getOptionLabelUsing(fn($value): ?string => Product::find($value)?->code . ' - ' . Product::find($value)?->name)
                    ->options(function () {
                        return Product::where('active', 1)
                            ->get()
                            ->mapWithKeys(fn($product) => [
                                $product->id => "{$product->code} - {$product->name}"
                            ]);
                    }),
                SelectFilter::make('branch_id')
                    ->label('Branch')->searchable()
                    ->options(Branch::whereIn('type', [Branch::TYPE_BRANCH, Branch::TYPE_CENTRAL_KITCHEN])
                        ->active()->pluck('name', 'id')),

                Filter::make('date_range')
                    ->form([
                        DatePicker::make('start_date')
                            ->label('Start Date'),
                        DatePicker::make('end_date')
                            ->label('End Date'),
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data): \Illuminate\Database\Eloquent\Builder {

                        return $query->when(
                            isset($data['start_date']) && isset($data['end_date']),
                            fn($query) => $query->whereBetween('orders.created_at', [$data['start_date'], $data['end_date']])
                        );
                    }),
            ], layout: FiltersLayout::AboveContent);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        // return static::getModel()::query()->orderBy('product');
        // // Extract filter values from the request
        $updates = request()->input('components.0.updates', []);
        $start_date = $updates['tableFilters.date_range.start_date'] ?? null;
        $end_date = $updates['tableFilters.date_range.end_date'] ?? null;
        // dd($updates, $start_date, $end_date);
        // Build the query using Eloquent
        $query = OrderDetails::query()
            ->select(
                'products.name AS product',
                'products.code AS code',
                'products.id AS product_id',
                'branches.name AS branch',
                'units.name AS unit',
                'orders_details.package_size AS package_size',
                DB::raw('SUM(orders_details.available_quantity) AS quantity'),
                'orders_details.price as unit_price',
                DB::raw('SUM(orders_details.available_quantity) * orders_details.price AS total_price')
            )
            ->join('products', 'orders_details.product_id', '=', 'products.id')
            ->join('orders', 'orders_details.order_id', '=', 'orders.id')
            ->join('branches', 'orders.branch_id', '=', 'branches.id')
            ->join('units', 'orders_details.unit_id', '=', 'units.id')
            ->whereNull('orders.deleted_at')
            ->when($start_date && $end_date, function ($query) use ($start_date, $end_date) {
                $query->whereBetween('orders.created_at', [$start_date, $end_date]);
            })
            // ->where('products.id', $product_id)
            ->groupBy(
                'orders.branch_id',
                'products.name',
                'products.code',
                'products.id',
                'branches.name',
                'units.name',
                'orders_details.package_size',
                'orders_details.price'
            )
            ->orderByRaw('NULL');
        return $query;
    }
}
