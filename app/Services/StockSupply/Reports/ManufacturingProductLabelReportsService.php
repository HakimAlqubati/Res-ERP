<?php

namespace App\Services\StockSupply\Reports;

use App\Models\InventoryTransaction;
use App\Models\Setting;
use App\Models\StockSupplyOrder;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

class ManufacturingProductLabelReportsService
{
    /**
     * Get label data report with filters and pagination.
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getLabelsReport(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = InventoryTransaction::query()
            ->where('transactionable_type', StockSupplyOrder::class)
            ->whereHas('store.branches', function ($q) {
                $q->where('type', \App\Models\Branch::TYPE_CENTRAL_KITCHEN);
            })
            ->with(['product.halalCertificate', 'unit']);

        // Apply filters
        if (!empty($filters['stock_supply_order_id'])) {
            $query->where('transactionable_id', $filters['stock_supply_order_id']);
        }

        if (!empty($filters['product_id'])) {
            if (is_array($filters['product_id'])) {
                $query->whereIn('product_id', $filters['product_id']);
            } else {
                $query->where('product_id', $filters['product_id']);
            }
        }

        if (!empty($filters['store_id'])) {
            $query->where('store_id', $filters['store_id']);
        }

        if (!empty($filters['category_id'])) {
            $query->whereHas('product', function ($q) use ($filters) {
                $q->where('category_id', $filters['category_id']);
            });
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('movement_date', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('movement_date', '<=', $filters['to_date']);
        }

        // Apply sorting (default latest)
        $query->latest('movement_date');

        // Check if global halal logo is enabled
        $useGlobalLogo = Setting::getSetting('use_global_halal_logo');
        $globalLogoPath = Setting::getSetting('global_halal_logo');
        $globalLogoUrl = ($useGlobalLogo && $globalLogoPath) ? Storage::url($globalLogoPath) : null;

        return $query->paginate($perPage)->through(function ($transaction) use ($globalLogoUrl) {
            $productionDate = $transaction->movement_date ? Carbon::parse($transaction->movement_date) : null;
            $expiryDate = $transaction->expiry_date; // Using the accessor we created

            // Patch Number: movement_date formatted as Ymd (e.g., 20241025)
            $patchNumber = $productionDate ? $productionDate->format('Ymd') : null;

            // Use global logo if set, otherwise per-product logo
            $halalLogo = $globalLogoUrl
                ?? ($transaction->product->halalCertificate?->halal_logo ? Storage::url($transaction->product->halalCertificate->halal_logo) : null);

            return [
                'transaction_id' => $transaction->id,
                'stock_supply_order_id' => $transaction->transactionable_id,
                'product_id' => $transaction->product_id,
                'product_name' => $transaction->product->name ?? null,
                'production_date' => $productionDate ? $productionDate->toDateString() : null,
                'expiry_date' => $expiryDate ? $expiryDate->toDateString() : null,
                'patch_number' => $patchNumber,
                'net_weight' => $transaction->product->halalCertificate?->net_weight,
                'quantity' => $transaction->quantity,
                'unit' => $transaction->unit?->name,
                'store_name' => $transaction->store?->name ?? null,
                'halal_logo' => $halalLogo,
                'allergen_info' => $transaction->product->halalCertificate?->allergen_info ?? '',
            ];
        });
    }

    /**
     * Get detailed label data for a specific product and batch.
     *
     * @param int $productId
     * @param string $batchCode
     * @return array|null
     */
    public function getLabelDetails(int $productId, string $batchCode): ?array
    {
        // Batch code is Ymd (e.g., 20250125). Convert to Y-m-d to match movement_date.
        try {
            $date = Carbon::createFromFormat('Ymd', $batchCode)->startOfDay();
        } catch (\Exception $e) {
            return null;
        }

        // Find the transaction for this product and date (batch)
        $transaction = InventoryTransaction::with(['product.halalCertificate'])
            ->where('product_id', $productId)
            ->whereDate('movement_date', $date)
            ->where('transactionable_type', StockSupplyOrder::class)
            ->with(['store.branches' => function ($q) {
                // Optional: ensure it's from a manufacturing branch if strictness is required
                // But the calling code might just want details for a printed label
            }])
            ->latest('id') // If multiple transactions for same product/day, take the latest
            ->first();

        if (!$transaction) {
            return null;
        }

        $productionDate = $transaction->movement_date ? Carbon::parse($transaction->movement_date) : null;
        $expiryDate = $transaction->expiry_date;
        $patchNumber = $batchCode; // Return the requested batch code

        // Fetch company settings
        $companyName = Setting::getSetting('company_name');
        $companyPhone = Setting::getSetting('company_phone');
        $companyAddress = Setting::getSetting('address');
        $countryCode = Setting::getSetting('default_nationality');
        $countryOfOrigin = getNationalitiesAsCountries()[$countryCode] ?? $countryCode;

        // Check if global halal logo is enabled
        $useGlobalLogo = Setting::getSetting('use_global_halal_logo');
        $globalLogoPath = Setting::getSetting('global_halal_logo');
        $halalLogo = ($useGlobalLogo && $globalLogoPath)
            ? Storage::url($globalLogoPath)
            : ($transaction->product->halalCertificate?->halal_logo ? Storage::url($transaction->product->halalCertificate->halal_logo) : null);

        return [
            'product_name' => $transaction->product->name,
            'code' => $transaction->product->code,
            'batch_code' => $patchNumber,
            'production_date' => $productionDate ? $productionDate->format('d/m/Y') : null,
            'best_before' => $expiryDate ? $expiryDate->format('d/m/Y') : null,
            'net_weight' => $transaction->product->halalCertificate?->net_weight,
            'manufactured_by' => $companyName,
            'address' => $companyAddress,
            'tel' => $companyPhone,
            'country_of_origin' => $countryOfOrigin,
            'allergen_info' => $transaction->product->halalCertificate?->allergen_info,
            'halal_logo' => $halalLogo,
        ];
    }
}
