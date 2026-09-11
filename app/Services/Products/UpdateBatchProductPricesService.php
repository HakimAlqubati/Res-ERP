<?php

namespace App\Services\Products;

use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\ProductPriceHistory;
use App\Models\Unit;
use App\Models\UnitPrice;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateBatchProductPricesService
{
    /**
     * Data mapping based on the price sheet:
     * - Box Price = 990.00 (Package Size = 6.0 KG)
     * - KG Price  = 990.00 / 6 = 165.00 (Package Size = 1.0 KG)
     * - Product 311-013 & 311-016: KG Price = 100.00 (Package Size = 1.0 KG)
     *
     * Product Code => [ [ 'unit' => 'UnitName/Code', 'price' => float, 'package_size' => float ], ... ]
     */
    public const PRICE_MAP = [
        '311-001' => [
            ['unit' => 'KG',  'price' => 165.00, 'package_size' => 1.0], // 990 / 6
            ['unit' => 'Box', 'price' => 990.00, 'package_size' => 6.0],
        ],
        '311-002' => [
            ['unit' => 'KG',  'price' => 165.00, 'package_size' => 1.0], // 990 / 6
            ['unit' => 'Box', 'price' => 990.00, 'package_size' => 6.0],
        ],
        '311-003' => [
            ['unit' => 'KG',  'price' => 165.00, 'package_size' => 1.0], // 990 / 6
            ['unit' => 'Box', 'price' => 990.00, 'package_size' => 6.0],
        ],
        '311-004' => [
            ['unit' => 'KG',  'price' => 165.00, 'package_size' => 1.0], // 990 / 6
            ['unit' => 'Box', 'price' => 990.00, 'package_size' => 6.0],
        ],
        '311-005' => [
            ['unit' => 'KG',  'price' => 165.00, 'package_size' => 1.0], // 990 / 6
            ['unit' => 'Box', 'price' => 990.00, 'package_size' => 6.0],
        ],
        '311-006' => [
            ['unit' => 'KG',  'price' => 165.00, 'package_size' => 1.0], // 990 / 6
            ['unit' => 'Box', 'price' => 990.00, 'package_size' => 6.0],
        ],
        '311-007' => [
            ['unit' => 'KG',  'price' => 165.00, 'package_size' => 1.0], // 990 / 6
            ['unit' => 'Box', 'price' => 990.00, 'package_size' => 6.0],
        ],
        '311-008' => [
            ['unit' => 'KG',  'price' => 165.00, 'package_size' => 1.0], // 990 / 6
            ['unit' => 'Box', 'price' => 990.00, 'package_size' => 6.0],
        ],
        '311-009' => [
            ['unit' => 'KG',  'price' => 165.00, 'package_size' => 1.0], // 990 / 6
            ['unit' => 'Box', 'price' => 990.00, 'package_size' => 6.0],
        ],
        '311-010' => [
            ['unit' => 'KG',  'price' => 165.00, 'package_size' => 1.0], // 990 / 6
            ['unit' => 'Box', 'price' => 990.00, 'package_size' => 6.0],
        ],
        '311-011' => [
            ['unit' => 'KG',  'price' => 165.00, 'package_size' => 1.0], // 990 / 6
            ['unit' => 'Box', 'price' => 990.00, 'package_size' => 6.0],
        ],
        '311-012' => [
            ['unit' => 'KG',  'price' => 165.00, 'package_size' => 1.0], // 990 / 6
            ['unit' => 'Box', 'price' => 990.00, 'package_size' => 6.0],
        ],
        '311-013' => [
            ['unit' => 'KG',  'price' => 100.00, 'package_size' => 1.0],
        ],
        '311-014' => [
            ['unit' => 'KG',  'price' => 165.00, 'package_size' => 1.0], // 990 / 6
            ['unit' => 'Box', 'price' => 990.00, 'package_size' => 6.0],
        ],
        '311-015' => [
            ['unit' => 'KG',  'price' => 165.00, 'package_size' => 1.0], // 990 / 6
            ['unit' => 'Box', 'price' => 990.00, 'package_size' => 6.0],
        ],
        '311-016' => [
            ['unit' => 'KG',  'price' => 100.00, 'package_size' => 1.0],
        ],
    ];

    /**
     * Execute price updates across unit_prices and inventory_transactions.
     *
     * @param array|null $customData Optional custom mapping overriding default PRICE_MAP
     * @return array Summary of updated records
     * @throws Exception
     */
    public function execute(?array $customData = null): array
    {
        $data = $customData ?? self::PRICE_MAP;

        $report = [
            'products_processed'    => 0,
            'unit_prices_updated'   => 0,
            'transactions_updated'  => 0,
            'details'               => [],
            'warnings'              => [],
        ];

        DB::transaction(function () use ($data, &$report) {
            // Cache units for efficiency and fallback resolution
            $allUnits = Unit::all();

            foreach ($data as $productCode => $unitsList) {
                // 1. Locate product by code, product_code, or code_old_system
                $product = Product::where('code', $productCode)
                    ->orWhere('product_code', $productCode)
                    ->orWhere('code_old_system', $productCode)
                    ->first();

                if (!$product) {
                    $msg = "Product with code [{$productCode}] not found.";
                    $report['warnings'][] = $msg;
                    Log::warning("[UpdateBatchProductPricesService] " . $msg);
                    continue;
                }

                $report['products_processed']++;

                foreach ($unitsList as $unitData) {
                    $unitIdentifier = trim($unitData['unit']);
                    $newPrice       = (float) $unitData['price'];
                    $packageSize    = (float) ($unitData['package_size'] ?? 1.0);

                    // 2. Resolve unit
                    $unit = $this->resolveUnit($unitIdentifier, $allUnits);

                    if (!$unit) {
                        $msg = "Unit [{$unitIdentifier}] not found for product [{$productCode}].";
                        $report['warnings'][] = $msg;
                        Log::warning("[UpdateBatchProductPricesService] " . $msg);
                        continue;
                    }

                    // 3. Update or Create Eloquent UnitPrice record
                    $unitPriceRecord = UnitPrice::where('product_id', $product->id)
                        ->where('unit_id', $unit->id)
                        ->first();

                    if ($unitPriceRecord) {
                        $oldPrice = $unitPriceRecord->price;
                        $unitPriceRecord->update([
                            'price'         => $newPrice,
                            'selling_price' => $newPrice,
                            'package_size'  => $packageSize,
                        ]);
                        $report['unit_prices_updated']++;
                    } else {
                        // Create UnitPrice if not already attached
                        $oldPrice = 0;
                        $unitPriceRecord = UnitPrice::create([
                            'product_id'    => $product->id,
                            'unit_id'       => $unit->id,
                            'price'         => $newPrice,
                            'selling_price' => $newPrice,
                            'package_size'  => $packageSize,
                            'usage_scope'   => UnitPrice::USAGE_ALL,
                        ]);
                        $report['unit_prices_updated']++;
                    }

                    // 4. Update Inventory Transactions using Eloquent query
                    $transactionsQuery = InventoryTransaction::where('product_id', $product->id)
                        ->where('unit_id', $unit->id);

                    $txCount = $transactionsQuery->count();

                    if ($txCount > 0) {
                        $pricePerBaseUnit = ($packageSize > 0)
                            ? round($newPrice / $packageSize, 6)
                            : $newPrice;

                        $transactionsQuery->update([
                            'price'               => $newPrice,
                            'package_size'        => $packageSize,
                            'price_per_base_unit' => $pricePerBaseUnit,
                        ]);

                        $report['transactions_updated'] += $txCount;
                    }

                    $report['details'][] = [
                        'product_code'         => $productCode,
                        'product_name'         => $product->name,
                        'unit'                 => $unit->name ?? $unitIdentifier,
                        'package_size'         => $packageSize,
                        'old_price'            => $oldPrice,
                        'new_price'            => $newPrice,
                        'transactions_updated' => $txCount,
                    ];
                }
            }
        });

        Log::info('[UpdateBatchProductPricesService] Batch price update completed', $report);

        return $report;
    }

    /**
     * Resolve Unit model by name, code, or common aliases.
     */
    protected function resolveUnit(string $identifier, $allUnits): ?Unit
    {
        $normalized = strtolower(trim($identifier));

        // Exact name or code match from cached collection
        $unit = $allUnits->first(function ($u) use ($normalized, $identifier) {
            return strtolower(trim($u->name ?? '')) === $normalized
                || strtolower(trim($u->code ?? '')) === $normalized
                || trim($u->name ?? '') === $identifier
                || trim($u->code ?? '') === $identifier;
        });

        if ($unit) {
            return $unit;
        }

        // Aliases mapping
        $aliases = [
            'kg'  => ['kg', 'كيلو', 'كجم', 'كيلوجرام', 'كيلو غرام', 'kilo', 'kilogram'],
            'box' => ['box', 'كرتون', 'صندوق', 'علبة', 'بكس', 'كرتونة', 'boxes', 'carton'],
        ];

        foreach ($aliases as $key => $synonyms) {
            if (in_array($normalized, $synonyms, true) || $normalized === $key) {
                $matched = $allUnits->first(function ($u) use ($synonyms) {
                    $uName = strtolower(trim($u->name ?? ''));
                    $uCode = strtolower(trim($u->code ?? ''));
                    return in_array($uName, $synonyms, true) || in_array($uCode, $synonyms, true);
                });

                if ($matched) {
                    return $matched;
                }
            }
        }

        return null;
    }
}
