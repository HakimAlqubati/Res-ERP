<?php

namespace App\Filament\Resources\ProductResource\Support;

use App\Models\OrderDetails;
use App\Models\PurchaseInvoiceDetail;
use App\Models\InventoryTransaction;
use App\Models\StockIssueOrderDetail;
use App\Models\GoodsReceivedNoteDetail;
use App\Models\ProductItem;
use App\Models\UnitPrice;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;

/**
 * Helper/Support static methods used by ProductResource and its Schema.
 * الهدف: تجميع دوال التحقّق/الحساب في مكان واحد نظيف.
 */
final class ProductResourceActions
{
     /**
     * Recalculate unit prices based on the updated basic price.
     *
     * @param float $basicPrice
     * @param int $mainUnitId
     * @return array
     */

    public static function updateFinalPriceEachUnit($set, $get, $state, $withOut = false)
    {
        // 🔄 Calculate the new total net price of product items
        $totalNetPrice = collect($state)->sum('total_price_after_waste') ?? 0;

        // 🔄 Retrieve existing units
        if ($withOut) {
            $units = $get('units') ?? [];
        } else {
            $units = $get('../../units') ?? [];
        }
        $updatedUnits = array_map(function ($unit) use ($totalNetPrice) {
            $packageSize = $unit['package_size'] ?? 1;
            $basePrice   = $packageSize * $totalNetPrice;
            $markup      = 1;
            return array_merge($unit, [
                'price'         => round($basePrice, 4),
                'selling_price' => round($basePrice, 4),
            ]);
        }, $units);

        // 🔄 Replace the `units` array completely
        if ($withOut) {
            $set('units', $updatedUnits);
        } else {
            $set('../../units', $updatedUnits);
        }
    }

    public static function validateUnitDeletion($unitPriceRecordId, ?Model $record = null): void
    {
        $productId = $record?->id ?? null;

        if (! $productId) {
            showWarningNotifiMessage(__('⚠️ Missing product or unit information.'));
            throw new Halt(__('⚠️ Missing product or unit information.'));
        }

        $isUsed =
            OrderDetails::where('product_id', $productId)->exists() ||
            PurchaseInvoiceDetail::where('product_id', $productId)->exists() ||
            InventoryTransaction::where('product_id', $productId)->exists() ||
            StockIssueOrderDetail::where('product_id', $productId)->exists();

        if ($isUsed) {
            showWarningNotifiMessage(__('⚠️ Cannot delete this unit because it is already used in orders, invoices, or inventory.'));
            throw new Halt(__('⚠️ Cannot delete this unit because it is already used.'));
        }
    }

    public static function validatePackageSizeChange($productId, $unitId, $newValue, callable $fail, ?Model $record = null): void
    {

        if (! $productId || ! $unitId) {
            return;
        }

        $unitPriceRecord = $record?->unitPrices()->where('unit_id', $unitId)->first();

        if (! $unitPriceRecord) {
            return;
        }

        $oldPackageSize = $unitPriceRecord->package_size ?? null;

        if ($oldPackageSize !== null && floatval($newValue) != floatval($oldPackageSize)) {
            $isUsed =
                OrderDetails::where('product_id', $productId)->where('unit_id', $unitId)->exists() ||
                PurchaseInvoiceDetail::where('product_id', $productId)->where('unit_id', $unitId)->exists() ||
                InventoryTransaction::where('product_id', $productId)->where('unit_id', $unitId)->exists() ||
                StockIssueOrderDetail::where('product_id', $productId)->where('unit_id', $unitId)->exists();

            if ($isUsed) {
                $fail(__('Package size modification is not allowed because this unit is already used in orders, invoices, or inventory.'));
            }
        }
    }
    public static function validateUnitsPackageSizeOrder(array $units, callable $fail = null): void
    {
        $filteredUnits = collect($units)
            ->filter(fn($unit) => ($unit['usage_scope'] ?? 'all') !== UnitPrice::USAGE_NONE)

            ->values(); // إعادة ترتيب الفهرس
        $packageSizes = $filteredUnits
            ->pluck('package_size')
            ->filter(fn($value) => $value !== null)
            ->map(fn($value) => floatval($value))
            ->values();

        $count = $packageSizes->count();

        if ($count === 0) {
            return;
        }

        // 1️⃣ التأكد من الترتيب من الأكبر إلى الأصغر
        for ($i = 1; $i < $count; $i++) {
            if ($packageSizes[$i] > $packageSizes[$i - 1]) {
                $message = __('⚠️ Package sizes must be sorted from largest to smallest.');
                if ($fail) {
                    // $fail($message);
                } else {
                    // showWarningNotifiMessage($message);
                }
                // return;
            }
        }

        // 2️⃣ التأكد أن آخر واحدة فقط = 1
        if ($packageSizes->last() !== 1.0) {
            $message = __('⚠️ The last qty per pack must be exactly 1.');
            if ($fail) {
                // $fail($message);
            } else {
                // showWarningNotifiMessage($message);
            }
            // return;
        }

        // 3️⃣ ممنوع أكثر من واحدة قيمتها = 1
        $oneCount = $packageSizes->filter(fn($size) => $size === 1.0)->count();
        if ($oneCount > 1) {
            $message = __('⚠️ Only one unit can have a package size of 1.');
            if ($fail) {
                $fail($message);
            } else {
                showWarningNotifiMessage($message);
            }
            return;
        }
    }

    public static function isProductLocked(
        $record,
        $unitPrice = null
    ): bool {
        if (! $record) {
            return false;
        }

        $productId = $record->id ?? null;
        if (! $productId) {
            return false;
        }

        return OrderDetails::where('product_id', $productId)
            ->where('unit_id', $unitPrice->unit_id)->exists()
            || PurchaseInvoiceDetail::where('product_id', $productId)
            ->where('unit_id', $unitPrice->unit_id)
            ->exists()
            || InventoryTransaction::where('product_id', $productId)
            ->where('unit_id', $unitPrice->unit_id)
            ->exists()
            || StockIssueOrderDetail::where('product_id', $productId)
            ->where('unit_id', $unitPrice->unit_id)
            ->exists()
            || GoodsReceivedNoteDetail::where('product_id', $productId)
            ->where('unit_id', $unitPrice->unit_id)
            ->exists()
            || ProductItem::where('product_id', $productId)
            ->where('unit_id', $unitPrice->unit_id)
            ->exists();
    }

    public static function shouldDisableUsageScopeOption(
        string $optionValue,
        mixed $record,
        mixed $product
    ): bool {
        if (! $record || ! $product) {
            return false;
        }

        $unitId    = $record->unit_id ?? null;
        $productId = $product->id ?? null;

        if (! $unitId || ! $productId) {
            return false;
        }

        // السماح دائمًا بالقيمة الحالية و بـ none
        $currentValue = $record->usage_scope ?? null;
        if ($optionValue === UnitPrice::USAGE_NONE || $optionValue === $currentValue) {
            return false;
        }

        // إذا الوحدة مستخدمة، نمنع تغيير الخيار لأي شيء آخر غير القيمة الحالية أو none
        $isUsed =
            OrderDetails::where('product_id', $productId)->where('unit_id', $unitId)->exists()
            || PurchaseInvoiceDetail::where('product_id', $productId)->where('unit_id', $unitId)->exists()
            || GoodsReceivedNoteDetail::where('product_id', $productId)->where('unit_id', $unitId)->exists()
            || InventoryTransaction::where('product_id', $productId)->where('unit_id', $unitId)->exists()
            || StockIssueOrderDetail::where('product_id', $productId)->where('unit_id', $unitId)->exists()
            || ProductItem::where('product_id', $productId)->where('unit_id', $unitId)->exists();

        return $isUsed;
    }
}