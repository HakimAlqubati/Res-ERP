<?php

declare(strict_types=1);

namespace App\Modules\Stock\Actions;

use App\Models\InventoryTransaction;
use App\Models\Store;
use App\Models\UnitPrice;
use App\Modules\Stock\Reports\FifoBatchReports\Contracts\GetAvailableStockBatchesQueryInterface;
use App\Modules\Stock\Reports\FifoBatchReports\DataTransferObjects\StockBatchFilterDTO;
use Illuminate\Support\Facades\DB;

final class SyncPriceOnNewStockEntryAction
{
    public function __construct(
        private readonly GetAvailableStockBatchesQueryInterface $stockBatchesQuery
    ) {}

    /**
     * تحديث أسعار جميع الوحدات عند دخول مخزون جديد (بشرط أن يكون المخزن السابق فارغاً).
     * * @param InventoryTransaction $transaction الحركة المخزنية الجديدة
     * @param Store $store كائن المخزن
     */
    public function execute(InventoryTransaction $transaction, Store $store): void
    {
        // 1. Guard: حماية التعديل ليتم فقط على المخزن الرئيسي
        if (! $store->default_store) {
            return;
        }

        // 2. Guard: برمجة دفاعية لضمان أن الحركة هي حركة "إدخال" فقط
        if ($transaction->movement_type !== InventoryTransaction::MOVEMENT_IN) {
            return;
        }

        // (اختياري) Guard: يمكنك هنا منع تحديث السعر إذا كان الدخول ناتجاً عن مرتجع مبيعات
        // if (class_basename($transaction->transactionable_type) === 'SalesReturn') { return; }

        // 3. جلب الباتش المتصدر الحالي عبر الاستعلام الموحد
        $reportResult = $this->stockBatchesQuery->execute(
            new StockBatchFilterDTO(
                storeId: $store->id,
                productIds: [$transaction->product_id],
                isCurrentBatch: true,
                perPage: null
            )
        );

        $currentBatch = $reportResult->batches->first();

        if (! $currentBatch) {
            return;
        }

        // 4. الشرط الذهبي (The Magic Condition):
        // هل الباتش المتصدر الآن هو نفسه الحركة الجديدة التي دخلت للتو؟
        // - إذا [نعم]: يعني أن المخزن كان صفراً، وهذا الباتش هو الوحيد، فيجب تحديث السعر.
        // - إذا [لا]: يعني أن هناك باتش أقدم منه لا يزال نشطاً، فيجب الخروج وعدم تغيير السعر.
        if ($currentBatch->id !== $transaction->id) {
            return;
        }

        // 5. تجهيز السعر والتحديث الآمن
        $pricePerPiece = (float) $currentBatch->unit_price;
        $unitPrices = UnitPrice::where('product_id', $transaction->product_id)->get();

        DB::transaction(function () use ($unitPrices, $pricePerPiece, $currentBatch) {
            $date = now();
            
            // صياغة الملاحظة مع توضيح سبب التحديث لتسهيل الـ Audit Trail
            $documentName = class_basename($currentBatch->transactionable_type) . ' #' . $currentBatch->transactionable_id;
            $notes = "Auto-updated: Price synced based on new stock entry (Previous stock was empty) from document {$documentName}";

            foreach ($unitPrices as $unitPrice) {
                $newPrice = round($pricePerPiece * $unitPrice->package_size, 4);

                // تحديث السعر فقط إذا كان مختلفاً عن السعر الحالي
                if ((float) $unitPrice->price !== $newPrice) {
                    $unitPrice->update([
                        'price' => $newPrice,
                        'date'  => $date,
                        'notes' => $notes,
                    ]);
                }
            }
        });
    }
}