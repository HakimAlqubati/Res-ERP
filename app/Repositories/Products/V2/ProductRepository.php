<?php

namespace App\Repositories\Products\V2;

use App\Models\Branch;
use App\Models\Order;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductRepository
{
    /**
     * جلب كميات الطلبات للمنتجات مع دعم الفلترة والتقسيم إلى صفحات (Pagination)
     */
    public function getProductsOrdersQuntitiesPaginated(Request $request)
    {
        // ✅ التحقق من المدخلات
        $request->validate([
            'product_id' => 'nullable|integer',
            'from_date'  => 'nullable|string',
            'to_date'    => 'nullable|string',
            'branch_id'  => 'nullable|integer|integer',  // IDs مفصولة بفواصل
            'page'       => 'nullable|integer|min:1',
            'per_page'   => 'nullable|integer|min:1|max:200',
        ]);

        // ✅ تحويل التاريخ إلى الصيغة الصحيحة Y-m-d
        $from_date = $request->input('from_date');
        $to_date   = $request->input('to_date');

        try {
            if ($from_date) {
                $from_date = Carbon::createFromFormat('d-m-Y', $from_date)->format('Y-m-d');
            }
            if ($to_date) {
                $to_date = Carbon::createFromFormat('d-m-Y', $to_date)->format('Y-m-d');
            }
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Invalid date format. Use d-m-Y.'], 422);
        }

        // ✅ تحديد الفروع
        if (function_exists('isBranchManager') && isBranchManager()) {
            $branch_id = [function_exists('getBranchId') ? getBranchId() : null];
        } else {
            $branch_id = explode(',', (string) $request->input('branch_id', ''));
        }

        if (empty(array_filter($branch_id))) {
            $branch_id = Branch::select('id')->selectable()->active()->pluck('id')->toArray();
        }

        // ✅ إعداد pagination
        $perPage = (int) $request->input('per_page', 15);
        $page    = (int) $request->input('page', 1);

        // ✅ الاستعلام الرئيسي
        $query = DB::table('orders_details')
            ->select(
                'orders.branch_id',
                'products.name AS product',
                'units.name AS unit',
                DB::raw('SUM(orders_details.available_quantity) AS quantity'),
                DB::raw('SUM(orders_details.available_quantity * orders_details.price) AS total_price')
            )
            ->join('products', 'orders_details.product_id', '=', 'products.id')
            ->join('orders', 'orders_details.order_id', '=', 'orders.id')
            ->join('branches', 'orders.branch_id', '=', 'branches.id')
            ->join('units', 'orders_details.unit_id', '=', 'units.id')
            ->when($request->filled('product_id'), function ($q) use ($request) {
                return $q->where('orders_details.product_id', $request->input('product_id'));
            })
            ->when($from_date && $to_date, function ($q) use ($from_date, $to_date) {
                return $q->whereBetween('orders.created_at', ["{$from_date} 00:00:00", "{$to_date} 23:59:59"]);
            })
            ->whereIn('orders.branch_id', $branch_id)
            ->whereIn('orders.status', [Order::DELEVIRED, Order::READY_FOR_DELEVIRY])
            ->whereNull('orders.deleted_at')
            ->groupBy('orders.branch_id', 'products.name', 'units.name')
            ->orderBy('orders.branch_id');

        // ✅ تنفيذ الـ pagination
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        // ✅ تنسيق البيانات
        $collection = $paginator->getCollection()->map(function ($item) {
            if (function_exists('formatQunantity')) {
                $item->quantity = formatQunantity($item->quantity);
            }
            if (function_exists('formatMoneyWithCurrency')) {
                $item->total_price = formatMoneyWithCurrency($item->total_price);
            }
            return $item;
        });
        $paginator->setCollection($collection);

        // ✅ تجهيز الإخراج بصيغة API واضحة
        return response()->json([
            'success'   => true,
            'data'      => $paginator->items(),
            'meta'      => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
            ],
            'links'     => [
                'next' => $paginator->nextPageUrl(),
                'prev' => $paginator->previousPageUrl(),
                'self' => $paginator->url($paginator->currentPage()),
            ],
        ]);
    }

    /**
     * مثال على دالة أخرى لاحقًا لو أردت استخدام مصدر بيانات آخر مثل Transactions
     */
    public function getReportDataFromTransactionsPaginated(Request $request)
    {
        // لاحقاً يمكنك نسخ المنطق أعلاه واستبدال الجداول بـ transactions
        return response()->json(['message' => 'Under development (transactions v2)']);
    }
}
