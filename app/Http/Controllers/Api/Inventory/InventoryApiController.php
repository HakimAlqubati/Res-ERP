<?php
// File: app/Http/Controllers/Api/Inventory/InventoryReportController.php
namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Services\Inventory\Reports\InventoryReport;
use App\Services\Inventory\Dto\InventoryFiltersDTO;

class InventoryApiController extends Controller
{
    public function __construct(private InventoryReport $report) {}
    // GET /api/inventory/remaining?product_id=&store_id=
    public function remaining(Request $r)
    {

        if (!$r->filled('store_id')) {
            return response()->json(['error' => 'store_id is required'], 400);
        }

        $storeId = (int) $r->integer('store_id');

        // 1) التزامن مع كل الطرق الغبية التي يرسل بها البشر البراميتر
        $raw = $r->input('product_ids', []);
        if (is_string($raw)) {
            // "1,2,3"
            $raw = array_filter(array_map('trim', explode(',', $raw)));
        }

        // 2) اجمع من product_ids[] أو string، وأضف product_id المفرد لو موجود
        $productIds = collect((array) $raw)
            ->merge($r->filled('product_id') ? [(int) $r->integer('product_id')] : [])
            ->map(fn($v) => (int) $v)
            ->filter(fn($v) => $v > 0)
            ->unique()
            ->values()
            ->all();

            
            $productIds = Product::select('id')->get()->pluck('id')->toArray();
            // dd($productIds);
        $filters = new InventoryFiltersDTO(
            storeId: $storeId, // إجباري
            categoryId: $r->filled('category_id') ? (int) $r->integer('category_id') : null,
            productIds: !empty($productIds) ? $productIds : null,
            perPage: null,
            page: null
        );
        $result = $this->report->run($filters);
        // dd($result);
        $data = $result['data'] ?? [];

        return response()->json([
            'product_id' => (int) $r->integer('product_id'),
            'store_id' => (int) $r->integer('store_id'),
            'data' => $data,
        ]);
    }

    // GET /api/inventory/filters
    public function filters()
    {
        // ضع مصادرك الحقيقية إن أردت. هذا مجرد هيكل بسيط.
        return response()->json([
            'categories' => [],
            'stores' => [],
            'movement_types' => ['in', 'out'],
        ]);
    }
}
