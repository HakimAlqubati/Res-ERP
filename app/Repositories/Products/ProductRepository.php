<?php

namespace App\Repositories\Products;

use stdClass;
use Exception;
use App\Filament\Resources\OrderReportsResource\GeneralReportOfProductsResource;
use App\Filament\Resources\OrderReportsResource\Pages\GeneralReportProductDetails;
use App\Http\Resources\ProductResource;
use App\Interfaces\Products\ProductRepositoryInterface;
use App\Models\Branch;
use App\Models\InventoryTransaction;
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
                $branch && $branch->type === Branch::TYPE_RESELLER,
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
            $otherBranchesCategories = Branch::centralKitchens()
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

        $query->whereNot('type', Product::TYPE_FINISHED_POS);
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
            // ->where('orders_details.product_id', '=', $request->input('product_id'))
            ->where(function ($query) use ($request) {
                $query->where('orders_details.product_id', '=', $request->input('product_id'))
                    ->orWhere('products.code', '=', $request->input('product_id'));
            })
            // ->when($from_date && $to_date, function ($query) use ($from_date, $to_date) {
            //     return $query->whereBetween('orders.created_at', [$from_date, $to_date]);
            // })

            ->when($from_date && $to_date, function ($query) use ($from_date, $to_date) {

                $s_d = date('Y-m-d', strtotime($from_date)) . ' 00:00:00';
                $e_d = date('Y-m-d', strtotime($to_date)) . ' 23:59:59';

                return $query->whereBetween('orders.transfer_date', [$s_d, $e_d]);
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
            $obj               = new stdClass();
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




    public function getReportDataFromTransactions($productParam, $from_date, $to_date, $branch_id)
    {
        $from = Carbon::parse($from_date)->startOfDay();
        $to   = Carbon::parse($to_date)->endOfDay();

        // 1) branch_id -> store_id(s)
        $branchIds = $branch_id ? (is_array($branch_id) ? $branch_id : [$branch_id]) : [];

        $storeIds = DB::table('branches')
            ->when($branchIds, fn($q) => $q->whereIn('id', $branchIds))
            ->pluck('store_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($storeIds)) {
            return [];
        }

        $IN  = InventoryTransaction::MOVEMENT_IN  ?? 'in';
        $OUT = InventoryTransaction::MOVEMENT_OUT ?? 'out';

        $q = DB::table('inventory_transactions as it')
            ->join('products as p', 'p.id', '=', 'it.product_id')
            ->leftJoin('branches as b', 'b.store_id', '=', 'it.store_id')
            ->leftJoin('stores as s', 's.id', '=', 'it.store_id')
            // الدخول فقط مربوط بطلب (Order) كمصدر
            ->leftJoin('orders as o', function ($j) use ($IN) {
                $j->on('o.id', '=', 'it.transactionable_id')
                    // ->where('it.transactionable_type', '=', Order::class)
                    ->where('it.movement_type', '=', $IN);
            })
            ->leftJoin('orders_details as od', function ($j) {
                $j->on('od.order_id', '=', 'o.id')
                    ->on('od.product_id', '=', 'it.product_id'); // مهم
            })
            ->leftJoin('units as u', 'u.id', '=', 'od.unit_id')

            // وحدة التقرير (اختياري): فقط لجلب package_size للوحدة المطلوبة
            ->leftJoin('unit_prices as rup', function ($j) {

                // إلغاء الربط عمليًا عندما لا توجد وحدة تقرير
                $j->on(DB::raw('1'), '=', DB::raw('0'));
            })

            ->where('it.deleted_at', null)

            // ->whereBetween('it.transaction_date', [$from, $to])
            // ->whereBetween('it.movement_date', [$from, $to])
            ->when($from_date && $to_date, fn($q) => $q->whereBetween('it.movement_date', [$from, $to]))
            ->whereIn('it.store_id', $storeIds)
            ->when($productParam, function ($q) use ($productParam) {
                $q->where(function ($w) use ($productParam) {
                    $w->where('p.id', $productParam)
                        ->orWhere('p.code', $productParam);
                });
            });

        $rows = $q->selectRaw("
        p.id   as product_id,
      MIN(CASE WHEN it.movement_type = ? THEN u.name END) AS unit_name,

        p.code as code,
        p.name as product,

        COALESCE(b.name, '') as branch,
        COALESCE(s.name, '') as store,

        -- حجم وحدة التقرير (إن وُجدت)، وإلا 1 (أي وحدة القاعدة)
        COALESCE(rup.package_size, 1.0) as report_ps,

        -- إجمالي الخروج والدخول بوحدة القاعدة
        SUM(CASE WHEN it.movement_type = ? THEN (it.quantity * COALESCE(it.package_size, 1.0)) ELSE 0 END) AS in_qty_base,
        SUM(CASE WHEN it.movement_type = ? THEN (it.quantity * COALESCE(it.package_size, 1.0)) ELSE 0 END) AS out_qty_base,

        -- الصافي بوحدة القاعدة
        SUM(
            CASE
                WHEN it.movement_type = ? THEN (it.quantity * COALESCE(it.package_size, 1.0))
                WHEN it.movement_type = ? THEN -(it.quantity * COALESCE(it.package_size, 1.0))
                ELSE 0
            END
        ) AS net_base,

        -- مجموع تكلفة الدخول فقط (من it) بوحدة القاعدة
        SUM(
            CASE
                WHEN it.movement_type = ?
                THEN (
                    ( NULLIF(it.price, 0) / NULLIF(COALESCE(it.package_size, 1.0), 0) )
                    * (it.quantity * COALESCE(it.package_size, 1.0))
                )
                ELSE 0
            END
        ) AS in_cost_sum_base 
    ", [$IN, $IN, $OUT, $IN, $OUT, $IN])

            ->groupBy(
                'p.id',
                'p.code',
                'p.name',
                'b.name',
                's.name',
                'rup.package_size'
            )
            ->get();
        // dd($rows);
        // dd($rows->toSql(),$rows->getBindings());

        // لو حابب تعرض النتائج بوحدة التقرير (إن وُجدت)، حوِّلها بعد الجلب:
        $rows = $rows->map(function ($r) {
            $ps = (float) ($r->report_ps ?: 1);
            $r->in_qty    = $ps ? (float)$r->in_qty_base  / $ps : (float)$r->in_qty_base;
            $r->out_qty   = $ps ? (float)$r->out_qty_base / $ps : (float)$r->out_qty_base;
            $r->net_qty   = $ps ? (float)$r->net_base     / $ps : (float)$r->net_base;
            // بإمكانك أيضًا إخفاء *_base إذا لا تحتاجها في الإخراج
            return $r;
        });
        // dd($rows[0]);
        // إخراج
        $final = [];
        foreach ($rows as $val) {
            $netBase       = (float) $val->net_base;
            $reportPs      = (float) ($val->report_ps ?: 1.0);
            $inQtyBase     = (float) $val->in_qty_base;
            $inCostSumBase = (float) $val->in_cost_sum_base;
            $outQty = (float) $val->out_qty_base;

            // الكمية بوحدة التقرير
            $netQtyOut = $reportPs > 0 ? ($netBase / $reportPs) : $netBase;

            // متوسط تكلفة الدخول للوحدة القاعدية
            $avgInCostPerBase = $inQtyBase > 0 ? ($inCostSumBase / $inQtyBase) : 0.0;

            // سعر وحدة التقرير = تكلفة/قاعدة × report_ps
            $unitPriceOut = $avgInCostPerBase * ($reportPs > 0 ? $reportPs : 1.0);

            $obj               = new stdClass();
            $obj->code      = $val->code;
            $obj->product      = $val->product;
            $obj->package_size = $reportPs; // حجم عبوة وحدة التقرير (إن طُلِبت)
            $obj->branch       = $val->branch;
            $obj->unit         = $val?->unit_name ?? '';
            // $obj->unit         = $reportUnitId ? ($reportUnitName ?? '') : ($val->trans_unit ?: 'base');
            $obj->quantity     = formatQunantity($netQtyOut);
            $obj->in_quantity = formatQunantity($inQtyBase);         // (الداخل − الخارج)
            $obj->out_quantity     = formatQunantity($outQty);            // (الداخل − الخارج)
            $obj->price        = formatMoneyWithCurrency($unitPriceOut); // متوسط تكلفة الدخول
            $final[]           = $obj;
        }

        return $final;
    }
    // 


    public function getReportDataFromTransactionsV2($productParam, $from_date, $to_date, $branchIds)
    {
        $from = $from_date ? Carbon::parse($from_date)->startOfDay() : null;
        $to   = $to_date   ? Carbon::parse($to_date)->endOfDay()   : null;
        $fromStr = $from ? $from->toDateTimeString() : null;
        $toStr   = $to   ? $to->toDateTimeString()   : null;


        // 2) استخراج store_ids المرتبطة بالفروع
        $storeIds = DB::table('branches')
            // ->when($branchIds, fn($q) => $q->whereIn('id', $branchIds))
            ->whereIn('id', $branchIds)
            ->pluck('store_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($storeIds)) {
            return [];
        }

        // 3) تجهيز فلتر المنتج
        $productId = null;
        if ($productParam !== null && $productParam !== '') {
            $productId = is_numeric($productParam)
                ? (int) $productParam
                : DB::table('products')->where('code', trim((string)$productParam))->value('id');
        }

        $productFilterSql = '';
        $productBindings  = [];
        if ($productId) {
            $productFilterSql = "AND it_in.product_id = ?";
            $productBindings  = [$productId];
        }

        // 4) SQL مع اسم الموزع لكل صف
        $placeholdersStores = implode(',', array_fill(0, count($storeIds), '?'));

        $sql = "
        SELECT 
            t.branch_name,
            t.unit_id,
            t.unit_name,
            t.package_size,
            t.unit_price,
            t.product_id,
            t.product_code,
            t.product_name,
            SUM(t.in_qty_base)  AS in_qty_base,
            SUM(t.out_qty_base) AS out_qty_base,
            SUM(t.remaining_qty_unit) AS remaining_qty_unit,
            SUM(t.remaining_value)    AS remaining_value
        FROM (
            SELECT
                it_in.id AS in_tx_id,
                it_in.movement_date,
                it_in.unit_id,
                u.name AS unit_name,
                COALESCE(it_in.package_size, 1.0) AS package_size,

                it_in.quantity * COALESCE(it_in.package_size, 1.0) AS in_qty_base,

                COALESCE(SUM(it_out.quantity * COALESCE(it_out.package_size, 1.0)), 0) AS out_qty_base,

                GREATEST(
                    it_in.quantity * COALESCE(it_in.package_size, 1.0)
                    - COALESCE(SUM(it_out.quantity * COALESCE(it_out.package_size, 1.0)), 0),
                    0
                ) / COALESCE(it_in.package_size, 1.0) AS remaining_qty_unit,

                CASE 
                    WHEN it_in.price IS NULL OR it_in.price = 0 
                    THEN COALESCE(up.price, 0)
                    ELSE it_in.price
                END AS unit_price,

                (
                    GREATEST(
                        it_in.quantity * COALESCE(it_in.package_size, 1.0)
                        - COALESCE(SUM(it_out.quantity * COALESCE(it_out.package_size, 1.0)), 0),
                        0
                    ) / COALESCE(it_in.package_size, 1.0)
                )
                *
                CASE 
                    WHEN it_in.price IS NULL OR it_in.price = 0 
                    THEN COALESCE(up.price, 0)
                    ELSE it_in.price
                END AS remaining_value,

                it_in.product_id,
                p.code AS product_code,
                p.name AS product_name,
                b.name AS branch_name

            FROM inventory_transactions AS it_in
            LEFT JOIN inventory_transactions AS it_out
              ON it_out.source_transaction_id = it_in.id
             AND it_out.movement_type = 'out'
             AND it_out.store_id = it_in.store_id
             AND it_out.deleted_at IS NULL
             AND (? IS NULL OR it_out.movement_date <= ?)

            JOIN products p ON p.id = it_in.product_id
            LEFT JOIN units  u ON u.id = it_in.unit_id
            LEFT JOIN unit_prices up
                   ON up.product_id = it_in.product_id
                  AND up.unit_id    = it_in.unit_id
            LEFT JOIN branches b
                   ON b.store_id = it_in.store_id

            WHERE it_in.deleted_at IS NULL
              AND it_in.movement_type = 'in'
              AND it_in.store_id IN ($placeholdersStores)
              {$productFilterSql}
              AND (? IS NULL OR it_in.movement_date >= ?)
              AND (? IS NULL OR it_in.movement_date <= ?)

            GROUP BY
              it_in.id, it_in.movement_date, it_in.unit_id, u.name,
              it_in.package_size, it_in.quantity, it_in.price, up.price,
              it_in.product_id, p.code, p.name, b.name
        ) AS t
        GROUP BY 
            t.branch_name, t.unit_id, t.unit_name, t.unit_price, 
            t.package_size, t.product_id, t.product_code, t.product_name
        ORDER BY t.branch_name, t.unit_id, t.package_size
    ";

        $bindings = array_merge(
            [$toStr, $toStr],            // لقيد it_out
            $storeIds,                   // المخازن
            $productBindings,            // المنتج
            [$fromStr, $fromStr, $toStr, $toStr] // قيد it_in
        );

        $rows = collect(DB::select($sql, $bindings));

        $final = [];
        foreach ($rows as $r) {
            if (($r->remaining_qty_unit ?? 0) <= 0) {
                continue;
            }
            $obj               = new \stdClass();
            $obj->code         = $r->product_code ?? '';
            $obj->product      = $r->product_name ?? '';
            $obj->branch       = $r->branch_name ?? '';   // <— اسم الموزع/الفرع
            $obj->package_size = (float)($r->package_size ?? 1);
            $obj->unit         = $r->unit_name ?? '';
            $obj->quantity     = formatQunantity((float)($r->remaining_qty_unit ?? 0));
            $obj->in_quantity  = formatQunantity((float)($r->in_qty_base ?? 0));
            $obj->out_quantity = formatQunantity((float)($r->out_qty_base ?? 0));
            $obj->price        = formatMoneyWithCurrency((float)($r->unit_price ?? 0));
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
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Invalid date format. Use d-m-Y.']);
        }

        if (isBranchManager()) {
            $branch_id = [getBranchId()];
        } else {
            $branch_id = explode(',', $request->input('branch_id'));
        }

        if (empty(array_filter($branch_id))) {
            // كل القيم داخل المصفوفة فارغة (مثل "", null, 0, إلخ)
            $branch_id = Branch::select('id')->selectable()->active()->pluck('id')->toArray();
        }
        // $dataQuantity = $this->getReportData($request, $from_date, $to_date, $branch_id);
        $dataQuantity2 = $this->getReportDataFromTransactions($request->product_id, $from_date, $to_date, $branch_id);
        return [
            // 'dataQuantity' => $dataQuantity,
            'dataQuantity' => $dataQuantity2,
            // 'd2' => $dataQuantity2,
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
