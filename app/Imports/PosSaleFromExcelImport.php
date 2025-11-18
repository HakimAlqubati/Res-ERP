<?php

namespace App\Imports;

use App\Models\PosSale;
use App\Models\PosSaleItem;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToCollection;

class PosSaleFromExcelImport implements ToCollection
{
    protected PosSale $sale;
    protected int $successfulImports = 0;

    public function __construct(
        protected int $branchId,
        protected int $storeId,
        protected ?string $saleDate = null,
        protected ?int $userId = null,
    ) {
        $this->userId = $this->userId ?: Auth::id();
    }

    /**
     * نتوقع نفس هيكلة ملف ItemSalesByClass:
     * col0 = Category
     * col1 = SubCategory
     * col2 = Product Name
     * col3 = Quantity (أو Grand Total)
     */
    public function collection(Collection $rows): void
    {
        // إنشاء سند POS واحد لهذا الملف
        $this->sale = PosSale::create([
            'branch_id'      => $this->branchId,
            'store_id'       => $this->storeId,
            'sale_date'      => $this->saleDate ?? now(),
            'status'         => PosSale::STATUS_DRAFT, // حتى يشتغل FIFO من الـ model
            'total_quantity' => 0,
            'total_amount'   => 0,
            'cancelled'      => false,
            'notes'          => 'Imported POS Sale from Excel',
            'created_by'     => $this->userId,
            'updated_by'     => $this->userId,
        ]);

        foreach ($rows as $index => $row) {
            // مثل الكلاس الأول: تخطي أول 3 صفوف كهيدر
            if ($index < 3) {
                continue;
            }

            $categoryName    = trim((string) ($row[0] ?? ''));
            $subcategoryName = trim((string) ($row[1] ?? ''));
            $productName     = trim((string) ($row[2] ?? ''));
            $qtyRaw          = $row[3] ?? null;

            // تخطي الصفوف الفارغة تماماً
            if ($categoryName === '' && $subcategoryName === '' && $productName === '') {
                continue;
            }

            // تخطي أي صف فيه كلمة Total
            if (
                Str::contains($categoryName, 'Total', true) ||
                Str::contains($subcategoryName, 'Total', true) ||
                Str::contains($productName, 'Total', true)
            ) {
                continue;
            }

            // لازم يكون فيه اسم منتج وكمية
            if ($productName === '' || $qtyRaw === null || $qtyRaw === '') {
                continue;
            }

            // جلب المنتج النهائي للـ POS (نفترض أنه تم إنشاؤه مسبقاً عن طريق ItemSalesByClassImport)
            $product = Product::where('name', $productName)
                ->where('type', Product::TYPE_FINISHED_POS)
                ->first();

            if (! $product) {
                // لو المنتج مش موجود، نتخطى الصف
                continue;
            }

            /**
             * الاعتماد على أول UnitPrice من العلاقة unitPrices
             * بدون البحث بالاسم، غالباً أول وحدة ستكون PIECE كما ضبطتها في الكلاس الآخر
             */
            $unitPrice = $product->unitPrices()->orderBy('id')->first();

            if (! $unitPrice) {
                // لو ما في أي UnitPrice، نتخطى هذا المنتج
                continue;
            }

            $quantity = (float) $qtyRaw;
            if ($quantity <= 0) {
                continue;
            }

            $price = (float) $unitPrice->price;
            $total = $quantity * $price;

            // إنشاء سطر POS
            PosSaleItem::create([
                'pos_sale_id'  => $this->sale->id,
                'product_id'   => $product->id,
                'unit_id'      => $unitPrice->unit_id,      // من UnitPrice
                'quantity'     => $quantity,
                'price'        => $price,
                'total_price'  => $total,
                'package_size' => $unitPrice->package_size, // غالباً 1
                'notes'        => null,
            ]);
            $this->successfulImports++;
        }

        // إعادة حساب الإجماليات من البنود
        $this->sale->load('items');
        $this->sale->recalculateTotals();
    }
    public function getSuccessfulImportsCount(): int
    {
        return $this->successfulImports;
    }
}
