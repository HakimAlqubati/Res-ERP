<?php

namespace App\Console\Commands;

use Throwable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\UnitPrice;
use App\Models\ProductPriceHistory;
use App\Services\ProductPriceHistoryService;
use Spatie\Multitenancy\Models\Tenant;

class UpdateProductUnitPrices extends Command
{
    protected $signature = 'products:update-unit-prices {--tenant=}';
    protected $description = 'Update unit_prices and record product price history for unmanufacturing products.';

    public function handle()
    {
        $tenantId = $this->option('tenant');

        if (isset($tenantId)) {

            $tenant = Tenant::find($tenantId);

            if (! $tenant) {
                $this->error("❌ Tenant with ID {$tenantId} not found.");
                return;
            }

            $tenant->makeCurrent(); // ✅ تشغيل التينانت
            $this->info("🏢 Tenant [{$tenant->id}] activated.");
        }
        $this->info("⏳ Starting unit price update...");

        DB::beginTransaction();

        try {
            // 🧹 1. حذف السجلات السابقة
            ProductPriceHistory::truncate();
            $this->warn("🗑️ Truncated product_price_histories");

            // 🔁 2. المنتجات غير المركبة
            $products = Product::active()->unmanufacturingCategory()->get();
            $service = new ProductPriceHistoryService();

            foreach ($products as $product) {
                $history = $service->getPriceHistory($product->id);

                if ($history->isEmpty()) {
                    $this->warn("🚫 No history for product: {$product->name}");
                    continue;
                }

                $latestPerUnit = [];

                foreach ($history as $record) {
                    $convertedPrices = $record['converted_unit_prices'] ?? [];

                    foreach ($convertedPrices as $converted) {
                        if (!$converted['converted_price']) {
                            continue;
                        }

                        $unitId = $converted['unit_id'];
                        $packageSize = $converted['package_size'];
                        $newPrice = $converted['converted_price'];
                        $oldPrice = ProductPriceHistory::where('product_id', $product->id)
                            ->where('unit_id', $unitId)
                            ->latest('id')
                            ->value('new_price');

                        if (is_null($oldPrice)) {
                            $oldPrice = $newPrice;
                        }


                        $readableType = preg_replace('/(?<!^)([A-Z])/', ' $1', $record['source_type']);

                        // 📝 سجل التاريخ
                        ProductPriceHistory::create([
                            'product_id'   => $product->id,
                            'unit_id'      => $unitId,
                            'old_price'    => $oldPrice,
                            'new_price'    => $newPrice,
                            'source_type'  => $record['source_type'],
                            'source_id'    => $record['source_id'],
                            'date'         => $record['date'],
                            'note' => "Price updated based on  {$readableType} #{$record['source_id']}",
                        ]);

                        $key = "{$unitId}_{$packageSize}";
                        $latestPerUnit[$key] = [
                            'unit_id' => $unitId,
                            'package_size' => $packageSize,
                            'price' => $newPrice,
                        ];
                    }
                }

                // ✅ تحديث أسعار الوحدة
                foreach ($latestPerUnit as $entry) {
                    UnitPrice::updateOrCreate(
                        [
                            'product_id' => $product->id,
                            'unit_id' => $entry['unit_id'],
                            'package_size' => $entry['package_size'],
                        ],
                        [
                            'price' => $entry['price'],
                        ]
                    );

                    $this->info("✅ Updated unit price for {$product->name} - unit ID {$entry['unit_id']} => price: {$entry['price']}");
                }
            }

            DB::commit();
            $this->info("🎉 All product price history saved and unit prices updated.");
        } catch (Throwable $e) {
            DB::rollBack();
            $this->error("❌ Transaction failed: " . $e->getMessage());
            report($e); // اختياري لتسجيل الخطأ في logs
        }
    }
}
