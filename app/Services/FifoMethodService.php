<?php

namespace App\Services;

use App\Models\InventoryTransaction;
use App\Models\PurchaseInvoiceDetail;
use App\Models\Unit;
use App\Services\InventoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FifoMethodService
{

    protected $sourceModel;

    public function __construct($sourceModel = null)
    {
        $this->sourceModel = $sourceModel;
    }
    public function allocateFIFO($productId, $unitId, $requestedQty, $sourceModel = null)
    {
        $inventoryService = new MultiProductsInventoryService();
        $inventoryReportProduct = $inventoryService->getInventoryForProduct($productId);
        $inventoryRemainingQty = collect($inventoryReportProduct)->firstWhere('unit_id', $unitId)['remaining_qty'] ?? 0;
        $targetUnit = \App\Models\UnitPrice::where('product_id', $productId)
            ->where('unit_id', $unitId)->with('unit')
            ->first();
        if (!$targetUnit) {
            Log::info("❌ Unit ID: $unitId not found for product ID: $productId.");
            throw new \Exception("❌ Unit ID: $unitId not found for product ID: $productId.");
        }

        // dd($requestedQty);
        $existingDetail = $sourceModel?->orderDetails()
            ->where('product_id', $productId)
            ->where('unit_id', $unitId)->first();
        if (
            setting('create_auto_order_when_stock_empty')
            && $existingDetail &&
            ($existingDetail->available_quantity == 0)
        ) {
            // ✅ البحث عن طلب معلق موجود لنفس الفرع والعميل
            $existingOrder = \App\Models\Order::where('customer_id', $sourceModel->customer_id)
                ->where('branch_id', $sourceModel->branch_id)
                ->where('status', \App\Models\Order::PENDING_APPROVAL)
                ->latest()->active()
                ->first();

            // ✏️ إذا لم يوجد، ننشئ طلب جديد
            if (!$existingOrder) {
                $existingOrder = \App\Models\Order::create([
                    'customer_id' => $sourceModel->customer_id,
                    'branch_id' => $sourceModel->branch_id,
                    'status' => \App\Models\Order::PENDING_APPROVAL,
                    'order_date' => now(),
                    'type' => \App\Models\Order::TYPE_NORMAL,
                    'notes' => "Auto-generated due to stock unavailability from Order #{$sourceModel?->id}",
                ]);

                Log::info("✅ Created new pending approval order #{$existingOrder->id} due to stock unavailability.");
            } else {
                Log::info("📌 Used existing pending approval order #{$existingOrder->id}.");
            }

            // ➕ إضافة أو تحديث التفاصيل للطلب المعلّق
            $existingDetail = $existingOrder->orderDetails()
                ->where('product_id', $productId)
                ->where('unit_id', $unitId)
                ->first();

            if ($existingDetail) {
                // 🔄 تحديث السطر الحالي إذا نفس الوحدة
                $existingDetail->update([
                    'quantity' => $existingDetail->quantity + $requestedQty,
                    'available_quantity' => $existingDetail->quantity + $requestedQty,
                    'updated_by' => auth()->id(),
                ]);
                Log::info("🔄 Updated existing order detail in pending order #{$existingOrder->id} (product_id: $productId, unit_id: $unitId).");
            } else {
                $previousOrderedQty = $sourceModel->orderDetails()
                    ->where('product_id', $productId)
                    ->where('unit_id', $unitId)
                    ->sum('quantity');

                $existingOrder->orderDetails()->create([
                    'product_id' => $productId,
                    'unit_id' => $unitId,
                    'quantity' => $previousOrderedQty,
                    'price' => getUnitPrice($productId, $unitId),
                    'package_size' => $targetUnit->package_size,
                    'created_by' => auth()->id(),
                    'is_created_due_to_qty_preivous_order' => true,
                    'previous_order_id' => $sourceModel->id,
                ]);
                Log::info("🆕 Created new order detail in pending order #{$existingOrder->id} for product_id: $productId, unit_id: $unitId.");
            }
        } else {
            if ($requestedQty > $inventoryRemainingQty) {

                $productName = $targetUnit->product->name ?? 'Unknown Product';
                $unitName = $targetUnit->unit->name ?? 'Unknown Unit';
                Log::info("❌ Requested quantity ($requestedQty) exceeds available inventory ($inventoryRemainingQty) for product: $productName (unit: $unitName)");
                throw new \Exception("❌ Requested quantity ($requestedQty'-'$unitName) exceeds available inventory ($inventoryRemainingQty) for product: $productName");
            }
        }
        return $this->getAllocateFifo($productId, $unitId, $requestedQty);
    }
    public function getAllocateFifo($productId, $unitId, $requestedQty)
    {
        $targetUnit = \App\Models\UnitPrice::where('product_id', $productId)
            ->where('unit_id', $unitId)->with('unit')
            ->first();
        $allocations = [];
        $entries = InventoryTransaction::where('product_id', $productId)
            ->where('movement_type', InventoryTransaction::MOVEMENT_IN)
            ->whereNull('deleted_at')
            ->orderBy('id', 'asc')
            ->get();
        $qtyBasedOnUnit = 0;
        foreach ($entries as $entry) {

            $previousOrderedQtyBasedOnTargetUnit = (InventoryTransaction::where('source_transaction_id', $entry->id)
                ->where('product_id', $productId)
                ->where('movement_type', InventoryTransaction::MOVEMENT_OUT)
                ->whereNull('deleted_at')
                ->sum(DB::raw('quantity'))
                * $entry->package_size) / $targetUnit->package_size;

            $entryQty = $entry->quantity;
            $qtyBasedOnUnit = (($entryQty * $entry->package_size) / $targetUnit->package_size);

            $remaining = $qtyBasedOnUnit - $previousOrderedQtyBasedOnTargetUnit;

            if ($remaining <= 0) continue;

            $deductQty = min($requestedQty, $remaining);

            if ($qtyBasedOnUnit <= 0) {
                continue;
            }
            if ($requestedQty <= 0) {
                break;
            }

            $price = ($entry->price * $targetUnit->package_size) / $entry->package_size;
            $price = round($price, 2);

            $notes = "Price is " . $price;
            if (isset($this->sourceModel)) {
                $notes = "Stock deducted for Order #{$this->sourceModel->id} from " .
                    $entry->transactionable_type .
                    " #" . $entry->transactionable_id .
                    " with price " . $price;
            }
            $allocation = [
                'transaction_id' => $entry->id,
                'store_id' => $entry->store_id,
                'unit_id' => $entry->unit_id,
                'target_unit_id' => $unitId,
                'target_unit_package_size' => $targetUnit->package_size,
                'entry_price' => $entry->price,
                'price_based_on_unit' => $price,
                'package_size' => $entry->package_size,
                'movement_date' => $entry->movement_date,
                'transactionable_id' => $entry->transactionable_id,
                'transactionable_type' => $entry->transactionable_type,
                'entry_qty' => $entryQty,
                'entry_qty_based_on_unit' => $qtyBasedOnUnit,
                'remaining_qty_based_on_unit' => $remaining,
                'notes' => $notes
            ];
            if (isset($this->sourceModel)) {
                $allocation['deducted_qty'] = $deductQty;
                $allocation['previous_ordered_qty_based_on_unit'] = $previousOrderedQtyBasedOnTargetUnit;
                $allocation['source_order_id'] = $this->sourceModel->id;
            }
            $allocations[] = $allocation;


            $requestedQty -= $deductQty;
        }

        return $allocations;
    }
}
