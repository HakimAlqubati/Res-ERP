<?php

declare(strict_types=1);

namespace App\Modules\Stock\Actions;

use App\Models\Store;
use App\Models\UnitPrice;
use App\Modules\Stock\Reports\FifoBatchReports\Contracts\GetAvailableStockBatchesQueryInterface;
use App\Modules\Stock\Reports\FifoBatchReports\DataTransferObjects\StockBatchFilterDTO;
use Illuminate\Support\Facades\DB;

final class SyncProductCurrentBatchPriceAction
{
    public function __construct(
        private readonly GetAvailableStockBatchesQueryInterface $stockBatchesQuery) {}

    /**
     * تحديث أسعار جميع الوحدات للمنتج بناءً على الباتش المتصدر الحالي.
     */
    public function execute(int $productId, Store $store): void
    {
        if (! $store->default_store) {
            return;
        }

        // 1. جلب النتيجة عبر الاستعلام الموحد (Query Object)
        $reportResult = $this->stockBatchesQuery->execute(
            new StockBatchFilterDTO(
                storeId: $store->id,
                productIds: [$productId],
                isCurrentBatch: true,
                perPage: null
            )
        );

        $currentBatch = $reportResult->batches->first();
        // الخروج إذا لم يتبقَ أي مخزون لهذا المنتج
        if (! $currentBatch) {
            return;
        }

        // 2. سعر القطعة (Price Per Piece) جاهز ومحسوب مسبقاً من الاستعلام
        $pricePerPiece = (float) $currentBatch->unit_price;

        // جلب جميع الوحدات الخاصة بالمنتج
        $unitPrices = UnitPrice::where('product_id', $productId)->get();

        // 3. التحديث داخل Transaction لضمان سلامة البيانات (ACID Compliance)
        DB::transaction(function () use ($unitPrices, $pricePerPiece, $currentBatch) {
            $date = now();
            $documentName = class_basename($currentBatch->transactionable_type).' #'.$currentBatch->transactionable_id;
            $notes = "Auto-updated: Price synced based on current batch from document {$documentName}";
            foreach ($unitPrices as $unitPrice) {
                // حساب السعر الجديد للوحدة الحالية بناءً على حجم العبوة
                $newPrice = round($pricePerPiece * $unitPrice->package_size, 4);

                // التحقق: نقوم بالتحديث فقط إذا اختلف السعر الفعلي لتقليل الاستعلامات
                if ((float) $unitPrice->price !== $newPrice) {
                    $unitPrice->update([
                        'price' => $newPrice,
                        'date' => $date,
                        'notes' => $notes,
                    ]);
                    // تحديث الموديل عبر update سيطلق الـ Observer الداخلي (booted)
                    // والذي سيقوم بتسجيل حركات السعر التاريخية (ProductPriceHistory) لكل وحدة بشكل مستقل!
                }
            }
        });
    }
}
