<?php

namespace App\Console\Commands;

use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\StockAdjustmentDetail;
use App\Models\StockInventoryDetail;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Multitenancy\Models\Tenant;

class FixStocktakeFifoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:fix-stocktake-fifo
                            {--tenant= : (Mandatory) Tenant ID to run the command under}
                            {--tx=65565 : Inventory Transaction ID of the stocktake deduction}
                            {--product=33 : Product ID}
                            {--unit=1 : Unit ID of Kg}
                            {--qty=2 : Correct deduction quantity in Kg}
                            {--dry-run : Run in simulation mode without saving changes to the database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retroactively fix stocktake unit from Box to Kg and re-allocate FIFO source IDs and running balances';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $tenantId = $this->option('tenant');

        // 1. التحقق الإجباري من التينانت
        if (! $tenantId) {
            $this->error('❌ خطأ: الخيار --tenant إجباري. يرجى تمرير معرف التينانت مثل: --tenant=1');
            return self::FAILURE;
        }

        if (! class_exists(Tenant::class)) {
            $this->error('❌ خطأ: حزمة Multitenancy غير متوفرة.');
            return self::FAILURE;
        }

        $tenant = Tenant::find($tenantId);
        if (! $tenant) {
            $this->error("❌ خطأ: لم يتم العثور على تينانت برقم [{$tenantId}].");
            return self::FAILURE;
        }

        $tenant->makeCurrent();
        $this->info("🏢 تم تفعيل التينانت بنجاح: [{$tenant->name}] (ID: {$tenant->id})");

        $txId        = (int) $this->option('tx');
        $productId   = (int) $this->option('product');
        $kgUnitId    = (int) $this->option('unit');
        $correctQty  = (float) $this->option('qty');
        $isDryRun    = (bool) $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('⚠️ الوضع التجريبي (Dry Run) مفعّل - لن يتم حفظ أي تعديلات في قاعدة البيانات.');
        }

        $this->info("🔍 فحص الحركة المخزنية #{$txId} للمنتج #{$productId}...");

        // 2. التحقق من وجود حركة الجرد
        $countTx = InventoryTransaction::withoutGlobalScopes()->find($txId);
        if (! $countTx) {
            $this->error("❌ لم يتم العثور على الحركة المخزنية #{$txId} داخل قاعدة بيانات التينانت.");
            return self::FAILURE;
        }

        if ((int) $countTx->product_id !== $productId) {
            $this->error("❌ الحركة المخزنية #{$txId} تابعة للمنتج #{$countTx->product_id} وليس المنتج المطلوب #{$productId}.");
            return self::FAILURE;
        }

        $storeId = (int) $countTx->store_id;

        // 3. جلب بيانات وحدة الكيلو للصنف
        $kgUnitPrice = DB::table('unit_prices')
            ->where('product_id', $productId)
            ->where('unit_id', $kgUnitId)
            ->first();

        $kgPackageSize = $kgUnitPrice ? (float) $kgUnitPrice->package_size : 1.0;
        $kgPrice       = $kgUnitPrice ? (float) $kgUnitPrice->price : (float) $countTx->price_per_base_unit;
        $kgPricePerBase = $kgUnitPrice && $kgPackageSize > 0
            ? round($kgPrice / $kgPackageSize, 6)
            : (float) $countTx->price_per_base_unit;

        // عرض تفاصيل الحركة الحالية والمستهدفة
        $this->table(
            ['الحقل', 'القيمة الحالية (السابقة)', 'القيمة الجديدة المستهدفة'],
            [
                ['Transaction ID', $countTx->id, $countTx->id],
                ['Product ID', $countTx->product_id, $productId],
                ['Store ID', $storeId, $storeId],
                ['Unit ID', $countTx->unit_id, $kgUnitId . ' (Kg)'],
                ['Package Size', $countTx->package_size, $kgPackageSize],
                ['Quantity', $countTx->quantity, $correctQty],
                ['Base Quantity', $countTx->base_quantity, round($correctQty * $kgPackageSize, 4)],
                ['Price', $countTx->price, $kgPrice],
                ['Price Per Base Unit', $countTx->price_per_base_unit, $kgPricePerBase],
            ]
        );

        DB::beginTransaction();

        try {
            // 4. تعديل حركة الجرد الأصلية
            $oldBaseQty = (float) $countTx->base_quantity;
            $newBaseQty = round($correctQty * $kgPackageSize, 4);

            $countTx->update([
                'unit_id'             => $kgUnitId,
                'package_size'        => $kgPackageSize,
                'quantity'            => $correctQty,
                'base_quantity'       => $newBaseQty,
                'price'               => $kgPrice,
                'price_per_base_unit' => $kgPricePerBase,
            ]);

            $this->info("✔ تم تحديث حركة الجرد #{$countTx->id} بنجاح.");

            // 5. تعديل سجلات الجرد والتسوية المقابلة (إن وجدت)
            if ($countTx->transactionable_type === StockAdjustmentDetail::class && $countTx->transactionable_id) {
                $adjDetail = StockAdjustmentDetail::find($countTx->transactionable_id);
                if ($adjDetail) {
                    $adjDetail->update([
                        'unit_id'      => $kgUnitId,
                        'package_size' => $kgPackageSize,
                        'quantity'     => $correctQty,
                    ]);

                    $invDetail = StockInventoryDetail::where('stock_inventory_id', $adjDetail->source_id)
                        ->where('product_id', $productId)
                        ->first();

                    if ($invDetail) {
                        $invDetail->update([
                            'unit_id'           => $kgUnitId,
                            'package_size'      => $kgPackageSize,
                            'physical_quantity' => $invDetail->system_quantity - $correctQty,
                            'difference'        => -$correctQty,
                        ]);
                        $this->info("✔ تم تحديث تفاصيل الجرد في StockInventoryDetail (الفارق أصبح -{$correctQty} كجم).");
                    }
                }
            }

            // 6. تجميع دفعات الوارد (Inbound Supplies Pool)
            $suppliesRaw = InventoryTransaction::withoutGlobalScopes()
                ->where('product_id', $productId)
                ->where('store_id', $storeId)
                ->where('movement_type', InventoryTransaction::MOVEMENT_IN)
                ->whereNull('deleted_at')
                ->orderBy('id', 'asc')
                ->get();

            $supplyBatches = [];
            foreach ($suppliesRaw as $sup) {
                $supBaseQty = (float) ($sup->base_quantity ?: ($sup->quantity * $sup->package_size));

                // الكميات المستهلكة من هذا التوريد قبل حركة الجرد
                $consumedBefore = (float) InventoryTransaction::withoutGlobalScopes()
                    ->where('source_transaction_id', $sup->id)
                    ->where('movement_type', InventoryTransaction::MOVEMENT_OUT)
                    ->where('id', '<', $countTx->id)
                    ->whereNull('deleted_at')
                    ->sum(DB::raw('IFNULL(base_quantity, quantity * package_size)'));

                $available = max(0, $supBaseQty - $consumedBefore);

                // إذا كان التوريد ما زال به رصيد وقت الجرد، أو دخل بعد الجرد
                if ($available > 0.0001 || $sup->id >= $countTx->id) {
                    $baseCost = $sup->price_per_base_unit ?: ($sup->price / max((float) $sup->package_size, 1));
                    $supplyBatches[] = (object) [
                        'id'                  => $sup->id,
                        'available_base_qty'  => $available,
                        'price_per_base_unit' => $baseCost,
                        'package_size'        => $sup->package_size,
                    ];
                }
            }

            $this->info("📦 تم العثور على " . count($supplyBatches) . " دفعة توريد (Batches) متاحة لاستهلاك FIFO.");

            // 7. جلب حركات الصادر بدءاً من حركة الجرد فصاعداً
            $outTransactions = InventoryTransaction::withoutGlobalScopes()
                ->where('product_id', $productId)
                ->where('store_id', $storeId)
                ->where('movement_type', InventoryTransaction::MOVEMENT_OUT)
                ->where('id', '>=', $countTx->id)
                ->whereNull('deleted_at')
                ->orderBy('transaction_date', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            $this->info("🚀 جاري إعادة تخصيص FIFO لـ " . $outTransactions->count() . " حركة صادر...");

            $batchIndex = 0;
            $reallocatedCount = 0;
            $splitCount = 0;

            foreach ($outTransactions as $outTx) {
                $neededBaseQty = (float) ($outTx->base_quantity ?: ($outTx->quantity * $outTx->package_size));

                while ($neededBaseQty > 0.0001 && $batchIndex < count($supplyBatches)) {
                    $currentBatch = &$supplyBatches[$batchIndex];

                    if ($currentBatch->available_base_qty <= 0.0001) {
                        $batchIndex++;
                        continue;
                    }

                    $takeBaseQty = min($neededBaseQty, $currentBatch->available_base_qty);
                    $pkgSize     = max((float) $outTx->package_size, 1);

                    if (abs($takeBaseQty - $neededBaseQty) < 0.0001) {
                        // الحالة 1: الدفعة تغطي كمية الحركة بالكامل
                        $outTx->update([
                            'source_transaction_id' => $currentBatch->id,
                            'price_per_base_unit'   => $currentBatch->price_per_base_unit,
                            'price'                 => round($currentBatch->price_per_base_unit * $pkgSize, 4),
                        ]);

                        // مزامنة حركة استلام الفرع في حال كان طلباً (Order)
                        if ($outTx->transactionable_type === Order::class) {
                            InventoryTransaction::withoutGlobalScopes()
                                ->where('transactionable_type', Order::class)
                                ->where('transactionable_id', $outTx->transactionable_id)
                                ->where('product_id', $productId)
                                ->where('movement_type', InventoryTransaction::MOVEMENT_IN)
                                ->update([
                                    'source_transaction_id' => $currentBatch->id,
                                ]);
                        }

                        $currentBatch->available_base_qty -= $takeBaseQty;
                        $neededBaseQty = 0;
                        $reallocatedCount++;
                    } else {
                        // الحالة 2: الدفعة تغطي جزءاً فقط -> استهلاك المتبقي وتقسيم السطر
                        $remainingForNext = $neededBaseQty - $takeBaseQty;

                        $outTx->update([
                            'quantity'              => round($takeBaseQty / $pkgSize, 4),
                            'base_quantity'         => $takeBaseQty,
                            'source_transaction_id' => $currentBatch->id,
                            'price_per_base_unit'   => $currentBatch->price_per_base_unit,
                            'price'                 => round($currentBatch->price_per_base_unit * $pkgSize, 4),
                        ]);

                        $currentBatch->available_base_qty = 0;
                        $batchIndex++;

                        // إنشاء سطر إضافي للكمية المتبقية بنفس بيانات الأصل
                        $clonedTx = $outTx->replicate();
                        $clonedTx->quantity      = round($remainingForNext / $pkgSize, 4);
                        $clonedTx->base_quantity = $remainingForNext;
                        $clonedTx->save();

                        $splitCount++;
                        $reallocatedCount++;

                        $outTx = $clonedTx;
                        $neededBaseQty = $remainingForNext;
                    }
                }
            }

            $this->info("✔ تم إعادة تخصيص {$reallocatedCount} سجل FIFO بنجاح (تم تقسيم {$splitCount} حركة جزئية).");

            // 8. إعادة احتساب الرصيد التراكمي (remaining_quantity) تسلسلياً
            $this->info("🔄 جاري إعادة احتساب الأرصدة التراكمية (remaining_quantity) للمخزن بالكامل...");

            $allTx = InventoryTransaction::withoutGlobalScopes()
                ->where('product_id', $productId)
                ->where('store_id', $storeId)
                ->whereNull('deleted_at')
                ->orderBy('transaction_date', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            $runningBase = 0.0;
            foreach ($allTx as $item) {
                $base = (float) ($item->base_quantity ?: ($item->quantity * $item->package_size));
                if ($item->movement_type === InventoryTransaction::MOVEMENT_IN) {
                    $runningBase += $base;
                } else {
                    $runningBase -= $base;
                }

                $pkg = max((float) $item->package_size, 1);
                DB::table('inventory_transactions')
                    ->where('id', $item->id)
                    ->update([
                        'remaining_quantity' => round($runningBase / $pkg, 4),
                    ]);
            }

            $this->info("✔ تم ضبط الأرصدة التراكمية بنجاح. الرصيد الحالي للصنف في المخزن بوحدة الأساس: " . round($runningBase, 4));

            if ($isDryRun) {
                DB::rollBack();
                $this->warn('⚠️ تم التراجع عن جميع التغييرات بنجاح لأن الأمر يعمل بوضع التجربة (--dry-run).');
            } else {
                DB::commit();
                $this->info('🎉 تم اعتماد وحفظ جميع التعديلات في قاعدة بيانات التينانت بنجاح!');
            }

            return self::SUCCESS;
        } catch (Exception $e) {
            DB::rollBack();
            $this->error('❌ حدث خطأ أثناء المعالجة وتم التراجع عن كافة التغييرات: ' . $e->getMessage());
            Log::error('FixStocktakeFifoCommand Failed', [
                'tenant_id'  => $tenantId,
                'tx_id'      => $txId,
                'product_id' => $productId,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);
            return self::FAILURE;
        }
    }
}
