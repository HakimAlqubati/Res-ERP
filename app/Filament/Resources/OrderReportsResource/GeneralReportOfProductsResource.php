<?php

namespace App\Filament\Resources\OrderReportsResource;

use App\Filament\Clusters\ReportOrdersCluster;
use App\Filament\Resources\OrderReportsResource\Pages\GeneralReportProductDetails;
use App\Filament\Resources\OrderReportsResource\Pages\ListGeneralReportOfProducts;
use App\Models\Branch;
use App\Models\Category;
use App\Models\FakeModelReports\GeneralReportOfProducts;
use App\Models\OrderDetails;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use \Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class GeneralReportOfProductsResource extends Resource
{
    protected static ?string $model = GeneralReportOfProducts::class;
    protected static ?string $slug = 'general-report-products';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $cluster = ReportOrdersCluster::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 1;
    /**
     * @deprecated Use `getModelLabel()` instead.
     */
    public static function getLabel(): ?string
    {
        return __('lang.general_report_of_products');
    }
    public static function getNavigationLabel(): string
    {
        return __('lang.general_report_of_products');
    }

    public static function getPluralLabel(): ?string
    {
        return __('lang.general_report_of_products');
    }


    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort(null)
            ->defaultView('filament.pages.order-reports.general-report-products')
            ->emptyStateHeading('Please choose a product')
            ->emptyStateDescription('Please choose a product or maybe there is no data')
            ->emptyStateIcon('heroicon-o-plus')
            ->columns([
                TextColumn::make('category_id'),
                TextColumn::make('available_quantity'),
                TextColumn::make('price'),
                TextColumn::make('total_price'),
            ])
            ->filters([
                SelectFilter::make("branch_id")
                    ->label(__('lang.branch'))
                    ->query(function (\Illuminate\Database\Eloquent\Builder $q, $data) {
                        return $q;
                    })->options(Branch::where('active', 1)
                        ->get()->pluck('name', 'id')),
                        SelectFilter::make("branch_id")
                                    ->label(__('lang.branch'))
                                    ->query(function (Builder $q, $data) {
                                        return $q;
                                    })->options(Branch::where('active', 1)
                                        ->get()->pluck('name', 'id')),
                Filter::make('date')
                    ->form([
                        DatePicker::make('start_date')
                            ->label(__('lang.start_date')),
                        DatePicker::make('end_date')
                            ->label(__('lang.end_date')),
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data): \Illuminate\Database\Eloquent\Builder {
                        return $query;
                    })
            ], layout: FiltersLayout::AboveContent)
            // ->query(fn() => self::getReportQuery())
            ;
    }



    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        // Start Eloquent query on the OrderDetail model
        $query = OrderDetails::query()
            ->join('orders', 'orders_details.order_id', '=', 'orders.id')
            ->join('products', 'orders_details.product_id', '=', 'products.id')
            ->select(
                'products.category_id',
                DB::raw('SUM(orders_details.available_quantity) as available_quantity'),
                'orders_details.price as price',
                DB::raw('SUM(orders_details.available_quantity) * orders_details.price as total_price')
            )
            // ->when($branch_id, function ($q) use ($branch_id) {
            //     return $q->where('orders.branch_id', $branch_id);
            // })
            // ->when($start_date && $end_date, function ($q) use ($start_date, $end_date) {
            //     $s_d = date('Y-m-d', strtotime($start_date)) . ' 00:00:00';
            //     $e_d = date('Y-m-d', strtotime($end_date)) . ' 23:59:59';

            //     return $q->whereBetween('orders.created_at', [$s_d, $e_d]);
            // })
            ->whereNull('orders.deleted_at')
            ->groupBy(
                'products.category_id',
                'orders_details.price',
                'orders_details.unit_id'
            );
        return $query;
        return static::getModel()::query();
    }



    public static function processReportData($start_date, $end_date, $branch_id) 
    {
        // Step 1: Get the query results
        $get_data = self::getEloquentQuery()->get();

        $data = [];
        $sum_price = 0;
        $sum_qty = 0;

        // Step 2: Aggregate data by category_id
        foreach ($get_data as $val) {
            if (!isset($data[$val->category_id])) {
                $data[$val->category_id] = [
                    'price' => 0,
                    'available_quantity' => 0,
                ];
            }
            $data[$val->category_id]['price'] += $val->total_price;
            $data[$val->category_id]['available_quantity'] += $val->available_quantity;
        }

        // Step 3: Get active categories
        $categories = Category::where('active', 1)->get(['id', 'name'])->pluck('name', 'id');

        $final_result['data'] = [];
        $total_price = 0;
        $total_quantity = 0;

        // Step 4: Build the final result
        foreach ($categories as $cat_id => $cat_name) {
            $obj = new \stdClass();
            $obj->category_id = $cat_id;
            $obj->url_report_details = "admin/general-report-products/details/$cat_id?start_date=$start_date&end_date=$end_date&branch_id=$branch_id&category_id=$cat_id";
            $obj->category = $cat_name;
            $obj->quantity = round(isset($data[$cat_id]) ? $data[$cat_id]['available_quantity'] : 0, 0);
            $price = (isset($data[$cat_id]) ? $data[$cat_id]['price'] : '0.00');
            $obj->price = formatMoney($price, getDefaultCurrency());
            $obj->amount = number_format($price, 2);
            $obj->symbol = getDefaultCurrency();
            $total_price += $price;
            $total_quantity += $obj->quantity;
            $final_result['data'][] = $obj;
        }

        // Step 5: Set total price and quantity
        $final_result['total_price'] = number_format($total_price, 2) . ' ' . getDefaultCurrency();
        $final_result['total_quantity'] = number_format($total_quantity, 2);

        return $final_result;
    }

    

    public static function getPages(): array
    {
        return [
            'index' => ListGeneralReportOfProducts::route('/'),
            'details' => GeneralReportProductDetails::route('/details/{category_id}'),
        ];
    }
}
