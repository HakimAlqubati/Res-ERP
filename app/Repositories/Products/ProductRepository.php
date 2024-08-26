<?php

namespace App\Repositories\Products;

use App\Http\Resources\ProductResource;
use App\Interfaces\Products\ProductRepositoryInterface;
use App\Models\Branch;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ProductRepository implements ProductRepositoryInterface
{

    protected $model;
    protected $currency;
    public function __construct(Product $model)
    {
        $this->model = $model;
        $this->currency = 'RM';
    }

    function index($request)
    {
        // Get the value of the ID and category ID filters from the request, or null if they're not set.
        $id = $request->input('id');
        $categoryId = $request->input('category_id');

        // Query the database to get all active products, or filter by ID and/or category ID if they're set.
        $products = Product::active()->HasUnitPrices()->when($id, function ($query) use ($id) {
            return $query->where('id', $id);
        })->when($categoryId, function ($query) use ($categoryId) {
            return $query->where('category_id', $categoryId);
        })->get();

        // Return a collection of product resources.
        return ProductResource::collection($products);
    }
    function report($request)
    {
        $from_date = $_GET['from_date'] ?? null;
        $to_date = $_GET['to_date'] ?? null;
        $month = $_GET['month'] ?? null;
        $year = $_GET['year'] ?? null;
        $branch_id = $_GET['branch_id'] ?? null;
        $strSelect = 'SELECT DISTINCT 
        products.id as product_id,
        products.name as product_name,
        orders_details.unit_id as unit_id,
        units.name as unit_name,
        COUNT(orders_details.product_id) as count,
        orders.branch_id as branch_id,
        branches.name as branch_name
        FROM 
        products 
        INNER JOIN orders_details ON (products.id = orders_details.product_id)
        INNER JOIN orders ON (orders.id = orders_details.order_id)
        inner join branches on (orders.branch_id = branches.id)
        INNER JOIN units ON (orders_details.unit_id = units.id)';
        $params = array();
        $where = array();

        $currnetRole = getCurrentRole();
        if ($currnetRole == 7) {
            $where[] = 'orders.customer_id = ?';
            $params[] = $request->user()->id;
        }
        if ($from_date && $to_date) {
            $where[] = 'DATE(orders.created_at) BETWEEN ? AND ?';
            $params[] = $from_date;
            $params[] = $to_date;
        }

        if ($year && $month) {
            $where[] = 'YEAR(orders.created_at) = ? AND MONTH(orders.created_at) = ?';
            $params[] = $year;
            $params[] = $month;
        }

        if ($branch_id) {
            $where[] = 'orders.branch_id = ?';
            $params[] = $branch_id;
        }
        if (!empty($where)) {
            $strSelect .= ' WHERE ' . implode(' AND ', $where);
        }
        $strSelect .= ' GROUP BY 
                products.id,
                products.name,
                orders_details.unit_id,
                units.name,
                orders.branch_id,
                branches.name
                ORDER BY 
                products.id ASC';
        $results = DB::select($strSelect, $params);
        return $results;
    }

    function reportv2($request)
    {
        $currnetRole =  getCurrentRole();
        if ($currnetRole == 7) {
            $branch_id = getBranchId();
        } else {
            $branch_id = $request->input('branch_id');
        }

        $from_date = $request->input('from_date');
        $to_date = $request->input('to_date');
        $year = $request->input('year');
        $month = $request->input('month');
        $product_id = $request->input('product_id');

        $data = DB::table('orders_details')
            ->join('orders', 'orders_details.order_id', '=', 'orders.id')
            ->join('products', 'orders_details.product_id', '=', 'products.id')
            ->select(
                'products.category_id',
                DB::raw('SUM(orders_details.available_quantity) as available_quantity'),
                DB::raw('SUM(orders_details.price) as price')
            )
            ->when($product_id, function ($query) use ($product_id) {
                return $query->where('orders_details.product_id', $product_id);
            })
            ->when($branch_id, function ($query) use ($branch_id) {
                return $query->where('orders.branch_id', $branch_id);
            })
            ->when($from_date && $to_date, function ($query) use ($from_date, $to_date) {
                return $query->whereBetween('orders.created_at', [$from_date, $to_date]);
            })
            ->when($year && $month, function ($query) use ($year, $month) {
                return $query->whereRaw('YEAR(orders.created_at) = ? AND MONTH(orders.created_at) = ?', [$year, $month]);
            })
            // ->whereIn('orders.status', [Order::DELEVIRED, Order::READY_FOR_DELEVIRY])
            ->where('orders.active', 1)
            ->whereNull('orders.deleted_at')
            ->groupBy('products.category_id')
            ->get()
            ->mapWithKeys(function ($item) {
                if (is_object($item)) {
                    return [$item->category_id => [
                        'available_quantity' => $item->available_quantity,
                        'price' => $item->price
                    ]];
                }
            })
            ->all();
        $categories = DB::table('categories')->where('active', 1)->get(['id', 'name'])->pluck('name', 'id');

        foreach ($categories as $cat_id => $cat_name) {
            $obj = new \stdClass();
            $obj->category_id = $cat_id;
            $obj->category_name = $cat_name;
            $obj->available_quantity =  round(isset($data[$cat_id]) ? $data[$cat_id]['available_quantity'] : 0, 0);
            $price = (isset($data[$cat_id]) ? $data[$cat_id]['price'] : '0.00');
            $obj->price =  formatMoney($price, $this->currency);
            $obj->amount = number_format($price, 2);
            $obj->symbol = $this->currency;
            $final_result[] = $obj;
        }

        return [
            'branches' => Branch::where('active', 1)->pluck('name', 'id'),
            'data' => $final_result
        ];
    }

    public function reportv2Details($request, $category_id)
    {
        $currnetRole =  getCurrentRole();

        if ($currnetRole == 7) {
            $branch_id = getBranchId();
        } else {
            $branch_id = $request->input('branch_id');
        }
        $from_date = $request->input('from_date');
        $to_date = $request->input('to_date');
        $year = $request->input('year');
        $month = $request->input('month');
        $product_id = $request->input('product_id');

        $data = DB::table('orders_details')
            ->join('orders', 'orders_details.order_id', '=', 'orders.id')
            ->join('products', 'orders_details.product_id', '=', 'products.id')
            ->join('units', 'orders_details.unit_id', '=', 'units.id')
            // ->select('products.category_id', 'orders_details.product_id as p_id' )
            ->when($branch_id, function ($query) use ($branch_id) {
                return $query->where('orders.branch_id', $branch_id);
            })
            ->when($product_id, function ($query) use ($product_id) {
                return $query->where('orders_details.product_id', $product_id);
            })
            ->when($from_date && $to_date, function ($query) use ($from_date, $to_date) {
                return $query->whereBetween('orders.created_at', [$from_date, $to_date]);
            })->when($year && $month, function ($query) use ($year, $month) {
                return $query->whereRaw('YEAR(orders.created_at) = ? AND MONTH(orders.created_at) = ?', [$year, $month]);
            })
            // ->whereIn('orders.status', [Order::DELEVIRED, Order::READY_FOR_DELEVIRY])
            ->where('orders.active', 1)
            ->whereNull('orders.deleted_at')
            ->where('products.category_id', $category_id)
            ->groupBy(
                'orders_details.product_id',
                'products.category_id',
                'orders_details.unit_id',
                'products.name',
                'units.name',
            )
            ->get([
                'products.category_id',
                'orders_details.product_id',
                DB::raw("IF(JSON_VALID(products.name), REPLACE(JSON_EXTRACT(products.name, '$." . app()->getLocale() . "'), '\"', ''), products.name) as product_name"),
                'units.name as unit_name',
                'orders_details.unit_id as unit_id',
                DB::raw('ROUND(SUM(orders_details.available_quantity), 0) as available_quantity'),
                DB::raw('(SUM(orders_details.price)) as price'),
            ]);
        $final_result = [];
        foreach ($data as   $val_data) {
            $obj = new \stdClass();
            $obj->category_id = $val_data->category_id;
            $obj->product_id = $val_data->product_id;
            $obj->product_name = $val_data->product_name;
            $obj->unit_name = $val_data->unit_name;
            $obj->unit_id = $val_data->unit_id;
            $obj->available_quantity = $val_data->available_quantity;
            $obj->price = formatMoney($val_data->price, $this->currency);
            $obj->amount = number_format($val_data->price, 2);
            $obj->symbol = $this->currency;
            $final_result[] = $obj;
        }
        return $final_result;
    }

    public function getProductsOrdersQuntities($request)
    {
        $currnetRole = getCurrentRole();
        $from_date = $request->input('from_date');
        $to_date = $request->input('to_date');
        if ($currnetRole == 7)
            $branch_id = [getBranchId()];
        else
            $branch_id = explode(',', $request->input('branch_id'));

        // dd($branch_id);
        $dataQuantity =  $this->getReportData($request, $from_date, $to_date, $branch_id);
        // dd($dataQuantity);
        return [
            'dataQuantity' => $dataQuantity,
            'dataTotal' => $this->getCount($request, $from_date, $to_date, $branch_id)
        ];
    }

    public function getReportData($request, $from_date, $to_date, $branch_id)
    {
        $data = DB::table('orders_details')
            ->select(
                'products.name AS product',
                'branches.name AS branch',
                'units.name AS unit',
                DB::raw('SUM(orders_details.available_quantity) AS quantity')
            )
            ->join('products', 'orders_details.product_id', '=', 'products.id')
            ->join('orders', 'orders_details.order_id', '=', 'orders.id')
            ->join('branches', 'orders.branch_id', '=', 'branches.id')
            ->join('units', 'orders_details.unit_id', '=', 'units.id')
            ->where('orders_details.product_id', '=', $request->input('product_id'))
            ->when($from_date && $to_date, function ($query) use ($from_date, $to_date) {
                return $query->whereBetween('orders.created_at', [$from_date, $to_date]);
            })
            ->when($branch_id && is_array($branch_id), function ($query) use ($branch_id) {
                return $query->whereIn('orders.branch_id', $branch_id);
            })
            // ->whereIn('orders.status', [Order::DELEVIRED, Order::READY_FOR_DELEVIRY])
            ->where('orders.active', 1)
            ->whereNull('orders.deleted_at')
            ->groupBy('orders.branch_id', 'products.name', 'branches.name', 'units.name')
            ->get();
        $final = [];
        foreach ($data as   $val) {
            $obj = new \stdClass();
            $obj->product = $val->product;
            $obj->branch = $val->branch;
            $obj->unit = $val->unit;
            $obj->quantity =   number_format($val->quantity, 2);
            $final[] = $obj;
        }
        return $final;
    }
    public function getCount($request, $from_date, $to_date, $branch_id)
    {
        $data = DB::table('orders_details')
            ->select(
                'products.name AS product',
                'units.name AS unit',
                DB::raw('SUM(orders_details.available_quantity) AS quantity')
            )
            ->join('products', 'orders_details.product_id', '=', 'products.id')
            ->join('orders', 'orders_details.order_id', '=', 'orders.id')
            ->join('units', 'orders_details.unit_id', '=', 'units.id')
            ->where('orders_details.product_id', '=', $request->input('product_id'))
            ->when($from_date && $to_date, function ($query) use ($from_date, $to_date) {
                return $query->whereBetween('orders.created_at', [$from_date, $to_date]);
            })
            ->when(getCurrentRole() == 7, function ($query) {
                return $query->where('orders.branch_id', getBranchId());
            })
            ->when(getCurrentRole() == 3 && $branch_id && is_array($branch_id), function ($query) use ($branch_id) {
                return $query->whereIn('orders.branch_id', $branch_id);
            })
            // ->whereIn('orders.status', [Order::DELEVIRED, Order::READY_FOR_DELEVIRY])
            ->where('orders.active', 1)
            ->whereNull('orders.deleted_at')
            ->groupBy('products.name',   'units.name')
            // ->groupBy('orders.branch_id')
            ->get();
        // Apply number_format() to the quantity value
        foreach ($data as &$item) {

            $item->quantity  = number_format($item->quantity, 2);
        }
        return $data;
    }
}
