<?php

namespace App\Services;

use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\Product;
use App\Models\PurchaseInvoiceDetail;
use App\Models\Store;
use App\Models\Unit;
use App\Models\UnitPrice;
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

    public function getAllocateFifo($productId, $unitId, $requestedQty)
    {
        $targetUnit = \App\Models\UnitPrice::where('product_id', $productId)
            ->where('unit_id', $unitId)->with('unit')
            ->first();
        $product = Product::find($productId);
        $storeId = defaultManufacturingStore($product)->id ?? null;

        $inventoryService = new MultiProductsInventoryService(null, $productId, $unitId,  $storeId);
        $inventoryReportProduct = $inventoryService->getInventoryForProduct($productId);

        $inventoryRemainingQty = collect($inventoryReportProduct)->firstWhere('unit_id', $unitId)['remaining_qty'] ?? 0;

        if ($requestedQty > $inventoryRemainingQty) {

            $productName = $targetUnit->product->name ?? 'Unknown Product';
            $unitName = $targetUnit->unit->name ?? 'Unknown Unit';
            Log::info("❌ Requested quantity ($requestedQty) exceeds available inventory ($inventoryRemainingQty) for product: $productName (unit: $unitName)");
            throw new \Exception("❌ Requested quantity ($requestedQty'-'$unitName) exceeds available inventory ($inventoryRemainingQty) for product: $productName");
        }

        $allocations = [];
        $entries = InventoryTransaction::where('product_id', $productId)
            ->where('movement_type', InventoryTransaction::MOVEMENT_IN)
            ->where('transactionable_type', '!=', Order::class)
            ->where('store_id', $storeId)
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get();

        $entryQtyBasedOnUnit = 0;


        foreach ($entries as $entry) {

            if (!$targetUnit) {
                continue;
            }


            $groupedOutQuantities = InventoryTransaction::where('source_transaction_id', $entry->id)
                ->where('product_id', $productId)
                ->where('movement_type', InventoryTransaction::MOVEMENT_OUT)
                ->whereNull('deleted_at')
                ->select('unit_id', 'package_size', DB::raw('SUM(quantity) as total_qty'))
                ->groupBy('unit_id', 'package_size')
                ->get();
            $previousOrderedQtyBasedOnTargetUnit = 0;

            foreach ($groupedOutQuantities as $group) {
                $groupQty = $group->total_qty;
                $groupPackageSize = $group->package_size;

                // تحويل الكمية إلى target unit
                $convertedQty = ($groupQty * $groupPackageSize) / $targetUnit->package_size;

                $previousOrderedQtyBasedOnTargetUnit += $convertedQty;
            }


            $entryQty = $entry->quantity;
            $entryQtyBasedOnUnit = (($entryQty * $entry->package_size) / $targetUnit->package_size);
            $remaining = $entryQtyBasedOnUnit - $previousOrderedQtyBasedOnTargetUnit;

            $remaining = round($remaining, 2);
            if ($remaining <= 0) continue;

            $deductQty = min($requestedQty, $remaining);

            $deductQty = round($deductQty, 2);
            if ($entryQtyBasedOnUnit <= 0) {
                continue;
            }
            if ($requestedQty <= 0) {
                break;
            }

            $price = ($entry->price * $targetUnit->package_size) / $entry->package_size;
            $price = round($price, 2);
            $notes = "Price is " . $price;
            // if (isset($this->sourceModel)) {
            $forModelName = str_replace(' ', '', class_basename($this->sourceModel));

            $notes = "Stock deducted for {$forModelName} #{$this->sourceModel?->id} from " .
                $entry->transactionable_type .
                " #" . $entry->transactionable_id .
                " with price " . $price;
            // }
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
                'entry_qty_based_on_unit' => $entryQtyBasedOnUnit,
                'remaining_qty_based_on_unit' => $remaining,
                'notes' => $notes
            ];

            // if (isset($this->sourceModel)) {
            $allocation['deducted_qty'] = $deductQty;
            $allocation['previous_ordered_qty_based_on_unit'] = $previousOrderedQtyBasedOnTargetUnit;
            $allocation['source_order_id'] = $this->sourceModel?->id;
            // }
            $allocations[] = $allocation;


            $this->updateUnitPricesFromSupply(
                $productId,
                $price,
                $targetUnit->package_size,
                $entry->movement_date,
                $notes
            );

            $requestedQty -= $deductQty;
        }

        return $allocations;
    }
    /**
     * تحديث أسعار الوحدات بناءً على حركة التوريد المستهلك منها.
     */
    protected function updateUnitPricesFromSupply(
        int $productId,
        float $orderedPrice,
        float $supplyPackageSize,
        $date,
        $notes
    ): void {
        $unitPrices = UnitPrice::where('product_id', $productId)->get();

        $pricePerPiece = $orderedPrice / $supplyPackageSize;

        foreach ($unitPrices as $unitPrice) {
            $newPrice = round($pricePerPiece * $unitPrice->package_size, 2);
            $unitPrice->update([
                'price' => $newPrice,
                'date' => $date,
                'notes' => $notes,
            ]);
        }
    }
}
