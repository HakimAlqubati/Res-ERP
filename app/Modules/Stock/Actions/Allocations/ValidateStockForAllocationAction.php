<?php

declare(strict_types=1);

namespace App\Modules\Stock\Actions\Allocations;

use App\Models\Store;
use App\Models\UnitPrice;
use App\Modules\Stock\Reports\StockBalanceReport\Contracts\StockBalanceRepositoryInterface;
use App\Modules\Stock\Reports\StockBalanceReport\DataTransferObjects\StockBalanceFilterDTO;
use Illuminate\Validation\ValidationException;

final class ValidateStockForAllocationAction
{
    /**
     * التحقق من توفر كميات كافية في المخزون لجميع المنتجات دفعة واحدة.
     * 
     * @param array<int, array{product_id: int, unit_id: int, qty: float}> $items
     * @param int $storeId
     * @throws ValidationException
     */
    public function execute(array $items, int $storeId): void
    {
        if (empty($items)) {
            return;
        }

        $productIds = array_unique(array_column($items, 'product_id'));
        $unitIds = array_unique(array_column($items, 'unit_id'));

        // 1. جلب أسعار الوحدات لتحويل الكميات المطلوبة إلى الوحدة الأساسية (Pieces)
        $unitPrices = UnitPrice::whereIn('product_id', $productIds)
            ->whereIn('unit_id', $unitIds)
            ->get()
            ->keyBy(fn ($up) => $up->product_id . '-' . $up->unit_id);

        $requiredQuantitiesInPieces = [];
        
        foreach ($items as $item) {
            $productId = (int) $item['product_id'];
            $unitId    = (int) $item['unit_id'];
            $qty       = (float) $item['qty'];

            $key = $productId . '-' . $unitId;
            $unitPrice = $unitPrices->get($key);

            if (! $unitPrice) {
                throw ValidationException::withMessages([
                    'stock' => "Unit (ID: {$unitId}) not found for product (ID: {$productId})"
                ]);
            }

            $qtyInPieces = $qty * $unitPrice->package_size;

            if (! isset($requiredQuantitiesInPieces[$productId])) {
                $requiredQuantitiesInPieces[$productId] = 0.0;
            }
            $requiredQuantitiesInPieces[$productId] += $qtyInPieces;
        }

        // 2. جلب الأسماء الأصلية للمنتجات
        $productNames = \App\Models\Product::withTrashed()
            ->whereIn('id', $productIds)
            ->pluck('name', 'id')
            ->toArray();

        // 3. جلب الأرصدة المتاحة من Repository التجميعي السريع جداً
        /** @var StockBalanceRepositoryInterface $stockBalanceRepo */
        $stockBalanceRepo = app(StockBalanceRepositoryInterface::class);
        $filters = new StockBalanceFilterDTO(
            storeId: $storeId,
            productIds: $productIds
        );
        $balances = $stockBalanceRepo->getBalances($filters)->keyBy('id');

        $shortages = [];

        // 4. التحقق من الكميات
        foreach ($requiredQuantitiesInPieces as $productId => $requiredPieces) {
            $balanceModel = $balances->get($productId);
            $availablePieces = 0.0;
            
            if ($balanceModel) {
                $availablePieces = (float) ($balanceModel->total_in ?? 0) - (float) ($balanceModel->total_out ?? 0);
            }

            // استخدام دالة التقريب لتفادي مشاكل الأرقام العشرية
            if (round($availablePieces, 4) < round($requiredPieces, 4)) {
                $name = $productNames[$productId] ?? "Product #{$productId}";
                $shortages[] = $name;
            }
        }

        // 5. إذا كان هناك عجز، نرمي استثناء يحتوي على أسماء المنتجات الناقصة
        if (! empty($shortages)) {
            $count = count($shortages);
            $displayed = array_slice($shortages, 0, 2);
            $message = "Not enough stock for: " . implode(", ", $displayed);
            
            if ($count > 2) {
                $message .= " (and " . ($count - 2) . " more)";
            }

            $storeName = Store::withTrashed()->where('id', $storeId)->value('name') ?? "Store #{$storeId}";
            $message .= " in '{$storeName}'";

            throw ValidationException::withMessages(['stock' => $message]);
        }
    }
}
