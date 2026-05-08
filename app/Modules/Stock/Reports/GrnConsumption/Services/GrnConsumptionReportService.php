<?php

namespace App\Modules\Stock\Reports\GrnConsumption\Services;

use App\Modules\Stock\Reports\GrnConsumption\Contracts\GrnConsumptionRepositoryInterface;
use App\Modules\Stock\Reports\GrnConsumption\DTOs\GrnConsumptionResultDTO;
use App\Modules\Stock\Reports\GrnConsumption\DTOs\GrnReportItemDTO;
use Illuminate\Pagination\LengthAwarePaginator;

class GrnConsumptionReportService
{
    public function __construct(
        private readonly GrnConsumptionRepositoryInterface $repository
    ) {}

    public function getReport(array $filters = [], int $perPage = 15)
    {
        // 1. جلب سندات الاستلام مع حركات الإدخال الخاصة بها
        $grns = $this->repository->getPaginatedGrns($filters, $perPage);
        
        $allInboundIds = [];
        $grnItemsMap = [];
        
        $grnCollection = $grns instanceof LengthAwarePaginator ? $grns->getCollection() : $grns;

        // تجميع كل معرفات الإدخال (IDs) لتجنب استعلامات N+1
        foreach ($grnCollection as $grn) {
            $grnItemsMap[$grn->id] = $grn->inventoryTransactions;
            foreach ($grn->inventoryTransactions as $inTx) {
                $allInboundIds[] = $inTx->id;
            }
        }

        // 2. جلب حركات الخروج (Out) التي تشير إلى حركات الإدخال في استعلام واحد
        $outTransactions = $this->repository->getOutboundTransactionsForInboundIds($allInboundIds);
        
        // 3. تجميع حركات الخروج بالذاكرة بناءً على مصدرها (source_transaction_id)
        $outTxGrouped = $outTransactions->groupBy('source_transaction_id');

        $results = [];

        // 4. دمج البيانات واحتساب المعادلات بناءً على حجم العبوة (Package Size)
        foreach ($grnCollection as $grn) {
            $inTransactions = $grnItemsMap[$grn->id] ?? collect();
            $mappedItems = [];
            $allCompleted = true; // نفترض اكتمال السند حتى يثبت العكس

            foreach ($inTransactions as $inTx) {
                // حساب الكمية الأساسية المدخلة
                $inPackageSize = max((float) $inTx->package_size, 1);
                $totalBaseIn = ((float) $inTx->quantity) * $inPackageSize;

                // حساب الكمية الأساسية المخرجة المرتبطة بهذا الإدخال (من حقل source_transaction_id)
                $relatedOuts = $outTxGrouped->get($inTx->id) ?? collect();
                $totalBaseOut = 0.0;
                
                foreach ($relatedOuts as $outTx) {
                    $outPackageSize = max((float) $outTx->package_size, 1);
                    $totalBaseOut += ((float) $outTx->quantity) * $outPackageSize;
                }

                // حساب الكمية المتبقية الأساسية
                $remainingBaseQty = max(0, $totalBaseIn - $totalBaseOut);
                
                // تحويل المتبقي ليعرض بالوحدة الأصلية للإدخال
                $remainingQty = round($remainingBaseQty / $inPackageSize, 4);

                $hasStartedLeaving = $totalBaseOut > 0;
                $isCompleted = $remainingBaseQty <= 0;

                if (!$isCompleted) {
                    $allCompleted = false; // إذا كان هناك منتج واحد غير مكتمل، فالسند غير مكتمل
                }

                $mappedItems[] = new GrnReportItemDTO(
                    productId: $inTx->product_id,
                    productName: $inTx->product->name ?? 'Unknown',
                    unitName: $inTx->unit->name ?? 'Unknown',
                    entryQuantity: (float) $inTx->quantity,
                    packageSize: $inPackageSize,
                    entryDate: $inTx->transaction_date ?? $inTx->movement_date,
                    remainingQuantity: $remainingQty,
                    hasStartedLeaving: $hasStartedLeaving,
                    isCompleted: $isCompleted
                );
            }

            // إذا كان السند لا يحتوي على أصناف (شاذ)، نعتبره غير مكتمل لتجنب أخطاء العرض
            if ($inTransactions->isEmpty()) {
                $allCompleted = false; 
            }

            $isLinkedToInvoice = !is_null($grn->purchase_invoice_id);
            
            $results[] = new GrnConsumptionResultDTO(
                grnId: $grn->id,
                grnNumber: $grn->grn_number ?? (string) $grn->id,
                grnDate: $grn->grn_date ? $grn->grn_date->format('Y-m-d') : null,
                isLinkedToInvoice: $isLinkedToInvoice,
                invoiceNumber: $isLinkedToInvoice ? $grn->purchaseInvoice?->invoice_no : null,
                items: $mappedItems,
                isFullyCompleted: $allCompleted
            );
        }

        // إرجاع النتيجة مع الاحتفاظ بالـ Pagination إذا كان موجوداً
        if ($grns instanceof LengthAwarePaginator) {
            return $grns->setCollection(collect($results));
        }

        return collect($results);
    }

    /**
     * Get the report flattened (each row is a product with its GRN details).
     */
    public function getFlattenedReport(array $filters = [], int $perPage = 15)
    {
        $paginatedGrns = $this->getReport($filters, $perPage);
        
        $flattened = [];
        $items = $paginatedGrns instanceof LengthAwarePaginator ? $paginatedGrns->items() : $paginatedGrns;

        foreach ($items as $grnResult) {
            foreach ($grnResult->items as $item) {
                $flattened[] = (object) [
                    'grn_number' => $grnResult->grnNumber,
                    'grn_date' => $grnResult->grnDate,
                    'is_linked_to_invoice' => $grnResult->isLinkedToInvoice,
                    'invoice_number' => $grnResult->invoiceNumber,
                    'product_name' => $item->productName,
                    'unit_name' => $item->unitName,
                    'entry_quantity' => $item->entryQuantity,
                    'remaining_quantity' => $item->remainingQuantity,
                    'entry_date' => $item->entryDate,
                    'is_completed' => $item->isCompleted,
                    'has_started_leaving' => $item->hasStartedLeaving,
                    'formatted_entry_date' => $item->formattedEntryDate,
                    'status_badge_class' => $item->statusBadgeClass,
                    'status_text' => $item->statusText,
                    'remaining_quantity_color' => $item->remainingQuantityColor,
                ];
            }
        }
        
        if ($paginatedGrns instanceof LengthAwarePaginator) {
            return $paginatedGrns->setCollection(collect($flattened));
        }

        return collect($flattened);
    }
}
