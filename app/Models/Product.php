<?php

namespace App\Models;

use App\Services\MultiProductsInventoryService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\Product\HasScopedUnitPrices;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Translatable\HasTranslations;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf  as PDF;

class Product extends Model implements Auditable
{
    use HasFactory,
        SoftDeletes,
        \OwenIt\Auditing\Auditable
        // , HasTranslations
        ,
        HasScopedUnitPrices;
    // public $translatable = ['name', 'description'];

    protected $fillable = [
        'id',
        'name',
        'code',
        'description',
        'active',
        'category_id',
        'product_code',
        'category_code',
        'main_unit_id',
        'basic_price',
        'minimum_stock_qty',
        'waste_stock_percentage',
        'sku'
    ];
    protected $auditInclude = [
        'name',
        'code',
        'description',
        'active',
        'category_id',
        'product_code',
        'category_code',
        'main_unit_id',
        'basic_price',
        'minimum_stock_qty',
        'waste_stock_percentage',
        'sku',
    ];
    protected $appends = ['unit_prices_count', 'product_items_count', 'is_manufacturing', 'formatted_unit_prices', 'display_name'];

    /**
     * Scope to filter products with at least 2 unit prices.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeWithMinimumUnitPrices($query, $count = 2)
    {
        return $query->withCount('unitPrices') // Count unitPrices
            ->having('unit_prices_count', '>=', $count); // Filter based on the count
    }
    public function units()
    {
        return $this->belongsToMany(Unit::class, 'unit_prices')
            ->withPivot('price');
    }

    public function unitPrices()
    {
        return $this->hasMany(UnitPrice::class)
            ->whereIn('usage_scope', [
                UnitPrice::USAGE_ALL,
                UnitPrice::USAGE_SUPPLY_ONLY,
                UnitPrice::USAGE_OUT_ONLY,
                UnitPrice::USAGE_MANUFACTURING_ONLY,
            ]);
    }
    public function allUnitPrices()
    {
        return $this->hasMany(UnitPrice::class);
    }

    public function unitsForOrders()
    {
        return $this->hasMany(UnitPrice::class)->where(
            'use_in_orders',
            1
        );
    }


    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function orderDetails()
    {
        return $this->hasMany(OrderDetails::class);
    }
    public function order_details()
    {
        return $this->hasMany(OrderDetails::class);
    }


    public function toArray()
    {
        return [
            'product_id' => $this->id,
            'product_name' => $this->name,
            'product_code' => $this->code,
            'description' => $this->description,
            'unit_prices' => $this->unitPrices,
            'product_items' => $this->productItems,
        ];
    }
    //new code
    public function scopeActive($query)
    {
        return $query->where('active', '=', 1);
    }

    // to return products that have unit prices only
    public function scopeHasUnitPrices($query)
    {
        return $query->has('unitPrices');
    }

    public function scopeHasProductItems($query)
    {
        return $query->has('productItems');
    }

    public function productItems()
    {
        return $this->hasMany(ProductItem::class, 'parent_product_id');
    }

    // Scope to return products belonging to manufacturing categories
    public function scopemanufacturingCategory($query)
    {
        return $query->whereHas('category', function ($query) {
            $query->where('is_manafacturing', true);
        });
    }
    public function scopeUnmanufacturingCategory($query)
    {
        return $query->whereHas('category', function ($query) {
            $query->where('is_manafacturing', false);
        });
    }

    /**
     * Relation to the Unit model for the main unit.
     */
    public function mainUnit()
    {
        return $this->belongsTo(Unit::class, 'main_unit_id');
    }

    /**
     * Get the final price as the sum of 'total_price' from related ProductItems.
     *
     * @return float
     */
    public function getFinalPriceAttribute()
    {
        return $this->productItems->sum('total_price_after_waste');
    }

    /**
     * Get the count of unit prices for the product.
     *
     * @return int
     */
    public function getUnitPricesCountAttribute()
    {
        return $this->unitPrices()->count();
    }

    /**
     * Get the count of product items for the product.
     *
     * @return int
     */
    public function getProductItemsCountAttribute()
    {
        return $this->productItems()->count();
    }

    /**
     * Check if the product belongs to a manufacturing category.
     *
     * @return bool
     */
    public function getIsManufacturingAttribute()
    {
        return (bool) optional($this->category)->is_manafacturing;
    }

    /**
     * Get unit prices as a comma-separated string.
     *
     * @return string
     */
    public function getFormattedUnitPricesAttribute()
    {
        return $this->unitPrices->map(function ($unitPrice) {
            $unitName = $unitPrice->unit->name ?? 'N/A';
            $price = $unitPrice->price ?? 0;
            $qtyPerPack = $unitPrice->package_size ?? '-';

            return "{$unitName} : {$price} (Qty per Pack: {$qtyPerPack})";
        })->implode(', ');
    }

    public static function generateProductCode($categoryId): string
    {
        $category = Category::find($categoryId);
        if (!$category || !$category->code_starts_with) {
            return '';
        }

        $prefix = $category->code_starts_with;

        // Get latest product with this prefix
        $lastProduct = static::withTrashed()
            ->where('category_id', $categoryId)
            ->where('code', 'like', $prefix . '%')
            ->orderBy('code', 'desc')
            ->first();

        $nextNumber = 1;
        if ($lastProduct) {
            $lastCode = (int)substr($lastProduct->code, strlen($prefix));
            $nextNumber = $lastCode + 1;
        }

        return $prefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }
    public function productPriceHistories()
    {
        return $this->hasMany(ProductPriceHistory::class, 'product_id');
    }
    public function getDisplayNameAttribute()
    {
        return "{$this->name} ({$this->code})";
    }
    public function inventoryTransactions()
    {
        return $this->hasMany(InventoryTransaction::class);
    }
    public function usedInProducts()
    {
        return $this->hasMany(ProductItem::class, 'product_id');
    }

    protected static function booted()
    {
        static::updating(function (Product $product) {
            // التحقق فقط إذا كان حقل 'active' هو الذي يتم تعديله إلى 'false'
            if ($product->isDirty('active') && $product->active === false) {

                // 1. جلب أصغر وحدة للمنتج (حسب package_size)
                // نستخدم allUnitPrices للتأكد من جلب كل الوحدات وليس فقط وحدات الفواتير
                $smallestUnit = $product->unitPrices()->orderBy('package_size', 'asc')->first();

                // إذا لم يكن للمنتج وحدات، لا يمكننا التحقق من المخزون، لذلك نتجاوز
                if (!$smallestUnit) {
                    return;
                }

                // 2. جلب المخزن الافتراضي للمنتجات المصنعة
                // نفترض أن لديك دالة مساعدة باسم defaultManufacturingStore()
                $storeId = defaultManufacturingStore($product)->id ?? null;

                if (!$storeId) {
                    // إذا لم يوجد مخزن افتراضي، لا يمكن المتابعة
                    throw new Exception('لا يمكن إلغاء تفعيل المنتج. لم يتم تحديد المخزن الافتراضي للمخزون.');
                }

                // 3. التحقق من الكمية المتبقية في المخزون
                // نفترض أن لديك كلاس MultiProductsInventoryService
                $inventoryService = new MultiProductsInventoryService(
                    null,
                    $product->id,
                    $smallestUnit->unit_id,
                    $storeId
                );

                $inventoryData = $inventoryService->getInventoryForProduct($product->id);
                $remainingQty = $inventoryData[0]['remaining_qty'] ?? 0;

                // 4. إذا كانت الكمية أكبر من صفر، أوقف العملية وأرجع رسالة خطأ
                if ($remainingQty > 0) {
                    // هذا الاستثناء (Exception) سيوقف عملية الحفظ
                    // وسيظهر الخطأ في واجهة المستخدم إذا تم التعامل معه بشكل صحيح
                    throw new Exception(
                        "لا يمكن إلغاء تفعيل المنتج '{$product->name}'. لا تزال هناك كمية في المخزون ({$remainingQty} {$smallestUnit->unit->name})."
                    );
                }
            }
        });
    }



    public function exportItemsPdf(): BinaryFileResponse
    {
        $items = DB::select("
        SELECT 
            parent.code as parent_code,
            parent.name AS parent_product,
            child.name AS item_product_name,
            units.name AS item_unit_name,
            pi.quantity AS item_quantity,
            pi.qty_waste_percentage AS waste_percentage,
            ROUND(pi.quantity * (1 + pi.qty_waste_percentage / 100), 2) AS item_quantity_after_waste
        FROM product_items pi
        JOIN products child ON pi.product_id = child.id
        JOIN products parent ON pi.parent_product_id = parent.id
        JOIN units ON pi.unit_id = units.id
        WHERE pi.product_id = ?
    ", [$this->id]);

        $pdf = Pdf::loadView('pdfs.product_items', [
            'items' => $items,
            'product' => $this,
        ]);

        return $pdf->download("product_items_{$this->id}.pdf");
    }

    public function scopeVisibleToBranch($query, Branch $branch)
    {
        if ($branch->type === Branch::TYPE_RESELLER) {
            $categoryIds = $branch->categories->pluck('id');
            return $query->whereIn('category_id', $categoryIds);
        }

        return $query;
    }
}
