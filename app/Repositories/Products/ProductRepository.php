<?php
namespace App\Repositories\Products;

use App\Filament\Resources\OrderReportsResource\GeneralReportOfProductsResource;
use App\Filament\Resources\OrderReportsResource\Pages\GeneralReportProductDetails;
use App\Http\Resources\ProductResource;
use App\Interfaces\Products\ProductRepositoryInterface;
use App\Models\Branch;
use App\Models\Order;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProductRepository implements ProductRepositoryInterface
{

    protected $model;
    protected $currency;
    public function __construct(Product $model)
    {
        $this->model    = $model;
        $this->currency = 'RM';
    }

    public function index($request)
    {
        // Get the value of the ID and category ID filters from the request, or null if they're not set.
        $id              = $request->input('id');
        $code            = $request->input('code');
        $categoryId      = $request->input('category_id');
        $isManufacturing = $request->input('is_manufacturing', false); // Default to true if not specified
        $branch = auth()->user()->branch ?? null;

        // Query the database to get all active products, or filter by ID and/or category ID if they're set.
        $query = Product::active()
        // ->when($isManufacturing, function ($query) {
        //     return $query->manufacturingCategory()->hasProductItems();
        // })
            ->when($isManufacturing, function ($query) {
                return $query->manufacturingCategory()
                // ->hasProductItems()
                ;
            }, function ($query) {
                // return $query->unmanufacturingCategory();
            })
            ->HasUnitPrices()
            ->when(
                $branch && $branch->type === \App\Models\Branch::TYPE_RESELLER,
                fn($query) => $query->visibleToBranch($branch)
            )
            ->with(['unitPrices' => function ($query) {
                $query->orderBy('order', 'asc');
            }])
            ->when($id, function ($query) use ($id) {
                return $query->where('id', $id);
            })
            ->when($code, function ($query) use ($code) {
                return $query->where('code', $code);
            })

            ->when($categoryId, function ($query) use ($categoryId) {
                return $query->where('category_id', $categoryId);
            });

        if (auth()->user()->branch && auth()->user()->branch->is_kitchen && $isManufacturing) {
            $customCategories        = auth()->user()?->branch?->categories()->pluck('category_id')->toArray() ?? [];
            $otherBranchesCategories = \App\Models\Branch::centralKitchens()
                ->where('id', '!=', auth()->user()?->branch?->id) // نستثني فرع المستخدم
                ->with('categories:id')
                ->get()
                ->pluck('categories')
                ->flatten()
                ->pluck('id')
                ->unique()
                ->toArray();

            if (count($customCategories) > 0) {
                $query->whereIn('category_id', $customCategories);
            }
            if (count($otherBranchesCategories) > 0) {
                $query->whereNotIn('category_id', $otherBranchesCategories);
            }
        }

        // $sql = vsprintf(str_replace('?', '%s', $query->toSql()), collect($query->getBindings())->map(function ($binding) {
        //     return is_numeric($binding) ? $binding : "'{$binding}'";
        // })->toArray());

        // dd($sql);
        $products = $query->get();

        // Return a collection of product resources.
        return ProductResource::collection($products);
    }
    public function report($request)
    {
        $from_date = $_GET['from_date'] ?? null;
        $to_date   = $_GET['to_date'] ?? null;
        $month     = $_GET['month'] ?? null;
        $year      = $_GET['year'] ?? null;
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
        $params = [];
        $where  = [];

        $currnetRole = getCurrentRole();
        if ($currnetRole == 7) {
            $where[]  = 'orders.customer_id = ?';
            $params[] = $request->user()->id;
        }
        if ($from_date && $to_date) {
            $where[]  = 'DATE(orders.created_at) BETWEEN ? AND ?';
            $params[] = $from_date;
            $params[] = $to_date;
        }

        if ($year && $month) {
            $where[]  = 'YEAR(orders.created_at) = ? AND MONTH(orders.created_at) = ?';
            $params[] = $year;
            $params[] = $month;
        }

        if ($branch_id) {
            $where[]  = 'orders.branch_id = ?';
            $params[] = $branch_id;
        }
        if (! empty($where)) {
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

    public function reportv2($request)
    {
        $currnetRole = getCurrentRole();
        if ($currnetRole == 7) {
            $branch_id = getBranchId();
        } else {
            $branch_id = $request->input('branch_id');
        }

        $from_date  = $request->input('from_date');
        $to_date    = $request->input('to_date');
        $year       = $request->input('year');
        $month      = $request->input('month');
        $product_id = $request->input('product_id');

        $data = GeneralReportOfProductsResource::processReportData($from_date, $to_date, $branch_id);

        return [
            'branches' => Branch::where('active', 1)->pluck('name', 'id'),
            'data'     => $data,
        ];
    }

    public function reportv2Details($request, $category_id)
    {
        $currnetRole = getCurrentRole();

        if ($currnetRole == 7) {
            $branch_id = getBranchId();
        } else {
            $branch_id = $request->input('branch_id');
        }
        $from_date  = $request->input('from_date');
        $to_date    = $request->input('to_date');
        $year       = $request->input('year');
        $month      = $request->input('month');
        $product_id = $request->input('product_id');

        $reportData = (new GeneralReportProductDetails())->getReportDetails($from_date, $to_date, $branch_id, $category_id);
        return $reportData;
    }

    public function getReportData($request, $from_date, $to_date, $branch_id)
    {
        $data = DB::table('orders_details')
            ->select(
                'products.name AS product',
                'products.code AS code',
                'products.id AS product_id',
                'branches.name AS branch',
                'units.name AS unit',
                'orders_details.package_size AS package_size',
                DB::raw('SUM(orders_details.available_quantity) AS quantity'),
                'orders_details.price as unit_price',
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
            ->whereIn('orders.status', [Order::DELEVIRED, Order::READY_FOR_DELEVIRY])
        // ->where('orders.active', 1)
            ->whereNull('orders.deleted_at')
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
            ->get();
        $final = [];
        foreach ($data as $val) {
            $obj               = new \stdClass();
            $obj->product      = $val->product;
            $obj->package_size = $val->package_size;
            $obj->branch       = $val->branch;
            $obj->unit         = $val->unit;
            $obj->quantity     = formatQunantity($val->quantity);
            $obj->price        = formatMoneyWithCurrency($val->unit_price);
            $final[]           = $obj;
        }
        return $final;
    }

    public function getProductsOrdersQuntities($request)
    {
        $currnetRole = getCurrentRole();
        $from_date   = $request->input('from_date');
        $to_date     = $request->input('to_date');

        try {
            if ($from_date) {
                $from_date = Carbon::createFromFormat('d-m-Y', $from_date)->format('Y-m-d');
            }

            if ($to_date) {
                $to_date = Carbon::createFromFormat('d-m-Y', $to_date)->format('Y-m-d');
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Invalid date format. Use d-m-Y.']);
        }

        if ($currnetRole == 7) {
            $branch_id = [getBranchId()];
        } else {
            $branch_id = explode(',', $request->input('branch_id'));
        }

        // dd($branch_id);
        $dataQuantity = $this->getReportData($request, $from_date, $to_date, $branch_id);
        // dd($dataQuantity);
        return [
            'dataQuantity' => $dataQuantity,
            'dataTotal'    => $this->getCount($request, $from_date, $to_date, $branch_id),
        ];
    }
    public function getCount($request, $from_date, $to_date, $branch_id)
    {
        $data = DB::table('orders_details')
            ->select(
                'products.name AS product',
                'units.name AS unit',
                DB::raw('SUM(orders_details.available_quantity) AS quantity'),
                DB::raw('SUM(orders_details.available_quantity * orders_details.price) AS price'),
            )
            ->join('products', 'orders_details.product_id', '=', 'products.id')
            ->join('orders', 'orders_details.order_id', '=', 'orders.id')
            ->join('branches', 'orders.branch_id', '=', 'branches.id')
            ->join('units', 'orders_details.unit_id', '=', 'units.id')
            ->where('orders_details.product_id', '=', $request->input('product_id'))
            ->when($from_date && $to_date, function ($query) use ($from_date, $to_date) {
                return $query->whereBetween('orders.created_at', [$from_date, $to_date]);
            })

            ->where('orders.branch_id', $branch_id)
            // ->when(getCurrentRole() == 3 && $branch_id && is_array($branch_id), function ($query) use ($branch_id) {
            //     //     dd($branch_id);
            //     return $query->whereIn('orders.branch_id', $branch_id);
            // })
            ->whereIn('orders.status', [Order::DELEVIRED, Order::READY_FOR_DELEVIRY])
        // ->where('orders.active', 1)
            ->whereNull('orders.deleted_at')
        // ->groupBy(
        //     'orders.branch_id',
        //     'products.name',
        //     'products.code',
        //     'products.id',
        //     'branches.name',
        //     'units.name',
        //     'orders_details.package_size',
        //     'orders_details.price'
        // )
            ->groupBy('orders.branch_id', 'products.name', 'units.name')
            ->get();
        // Apply number_format() to the quantity value
        foreach ($data as &$item) {

            $item->quantity = formatQunantity($item->quantity);
            $item->price    = formatMoneyWithCurrency($item->price);
        }
        return $data;
    }
}