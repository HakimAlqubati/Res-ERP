<?php

namespace App\Console\Commands;

use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockAdjustmentDetail;
use App\Models\StockInventoryDetail;
use App\Services\FifoMethodService;
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
    protected $description = 'Soft-delete transactions from stocktake onwards and cleanly replay them using FIFO engine';

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

        $this->info("🔍 فحص حركة الجرد المخزنية #{$txId} للمنتج #{$productId}...");

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

        // 3. جلب جميع الحركات من رقم حركة الجرد وما بعدها
        $transactionsToReplay = InventoryTransaction::withoutGlobalScopes()
            ->where('product_id', $productId)
            ->where('store_id', $storeId)
            ->where('id', '>=', $txId)
            ->whereNull('deleted_at')
            ->orderBy('id', 'asc')
            ->get();

        $this->info("📋 عدد الحركات التي سيتم عمل Soft Delete لها ثم إعادة إنشائها بالفيفو: " . $transactionsToReplay->count());

        // حساب الرصيد التراكمي السابق للحركة 65565
        $initialBaseBalance = (float) DB::table('inventory_transactions')
            ->where('product_id', $productId)
            ->where('store_id', $storeId)
            ->where('id', '<', $txId)
            ->whereNull('deleted_at')
            ->selectRaw("COALESCE(SUM(
                CASE 
                    WHEN movement_type = 'in' THEN IFNULL(base_quantity, quantity * package_size)
                    WHEN movement_type = 'out' THEN -IFNULL(base_quantity, quantity * package_size)
                    ELSE 0 
                END
            ), 0) as balance")
            ->value('balance');

        $this->line("   ℹ الرصيد التراكمي في النظام قبل حركة الجرد هو: {$initialBaseBalance} كجم (6 بوكسات).");
        $this->line("   ℹ المطلوب خصم {$correctQty} كجم بالجرد، ليصبح الرصيد بعد الجرد مباشرة: " . ($initialBaseBalance - $correctQty) . " كجم.");

        DB::beginTransaction();

        try {
            // 4. تعديل بيانات الجرد والتسوية في StockInventoryDetail و StockAdjustmentDetail
            $invDetailUpdated = false;
            if ($countTx->transactionable_type === StockAdjustmentDetail::class && $countTx->transactionable_id) {
                $adjDetail = StockAdjustmentDetail::find($countTx->transactionable_id);
                if ($adjDetail) {
                    $adjDetail->update([
                        'unit_id'         => $kgUnitId,
                        'package_size'    => 1,
                        'quantity'        => $correctQty,
                        'adjustment_type' => StockAdjustmentDetail::ADJUSTMENT_TYPE_DECREASE,
                    ]);

                    $invDetail = StockInventoryDetail::where('stock_inventory_id', $adjDetail->source_id)
                        ->where('product_id', $productId)
                        ->first();

                    if ($invDetail) {
                        $invDetail->update([
                            'unit_id'           => $kgUnitId,
                            'package_size'      => 1,
                            'system_quantity'   => $initialBaseBalance, // 30 كجم
                            'physical_quantity' => $initialBaseBalance - $correctQty, // 28 كجم
                            'difference'        => -$correctQty, // -2 كجم
                            'is_adjustmented'   => true,
                        ]);
                        $invDetailUpdated = true;
                        $this->info("✔ تم تعديل StockInventoryDetail: (النظام: {$initialBaseBalance} كجم، الفعلي: " . ($initialBaseBalance - $correctQty) . " كجم، الفارق: -{$correctQty} كجم).");
                    }
                    $this->info("✔ تم تعديل StockAdjustmentDetail: (الكمية: {$correctQty} كجم).");
                }
            }

            // 5. عمل Soft Delete لجميع الحركات من 65565 فصاعداً
            $idsToSoftDelete = $transactionsToReplay->pluck('id')->toArray();
            InventoryTransaction::whereIn('id', $idsToSoftDelete)->delete();
            $this->info("🗑 تم عمل Soft Delete لـ " . count($idsToSoftDelete) . " حركة مخزنية بنجاح.");

            // 6. إعادة إنشاء الحركات تسلسلياً باستخدام الفيفو
            $this->info("🔄 جاري إعادة إنشاء الحركات بالترتيب الصحيح عبر محرك الـ FIFO...");

            $recreatedInCount  = 0;
            $recreatedOutCount = 0;

            foreach ($transactionsToReplay as $index => $oldTx) {
                $this->line("   ▸ معالجة الحركة الأصلية #{$oldTx->id} [{$oldTx->movement_type}] تاريخ: {$oldTx->movement_date}...");

                // ─── الحالة الأولى: حركة الجرد رقم 65565 ───
                if ($oldTx->id === $countTx->id) {
                    $fifoService = new FifoMethodService();
                    $allocations = $fifoService->getAllocateFifo($productId, $kgUnitId, $correctQty, $storeId);

                    foreach ($allocations as $alloc) {
                        $newCountTx = InventoryTransaction::create([
                            'product_id'            => $productId,
                            'movement_type'         => InventoryTransaction::MOVEMENT_OUT,
                            'quantity'              => $alloc['deducted_qty'],
                            'unit_id'               => $alloc['target_unit_id'],
                            'package_size'          => $alloc['target_unit_package_size'],
                            'price'                 => $alloc['price_based_on_unit'],
                            'movement_date'         => $oldTx->movement_date,
                            'transaction_date'      => $oldTx->transaction_date,
                            'store_id'              => $storeId,
                            'notes'                 => "Stock adjustment (Corrected to {$correctQty} Kg) - Replayed via FIFO (Original Tx #{$oldTx->id})",
                            'transactionable_id'    => $oldTx->transactionable_id,
                            'transactionable_type'  => $oldTx->transactionable_type,
                            'source_transaction_id' => $alloc['transaction_id'],
                        ]);
                    }

                    $recreatedOutCount++;
                    $this->info("     ✔ تم إنشاء حركة الجرد كـ OUT بـ {$correctQty} كجم بالفيفو. الرصيد أصبح: 28 كجم.");
                    continue;
                }

                // ─── الحالة الثانية: الحركات الداخلة (IN) ───
                if ($oldTx->movement_type === InventoryTransaction::MOVEMENT_IN) {
                    // الحركات الداخلة يعاد إنشاؤها لتضاف إلى مسبح الفيفو فوراً
                    $newInTx = InventoryTransaction::create([
                        'product_id'             => $oldTx->product_id,
                        'movement_type'          => InventoryTransaction::MOVEMENT_IN,
                        'quantity'               => $oldTx->quantity,
                        'unit_id'                => $oldTx->unit_id,
                        'package_size'           => $oldTx->package_size,
                        'price'                  => $oldTx->price,
                        'movement_date'          => $oldTx->movement_date,
                        'transaction_date'       => $oldTx->transaction_date,
                        'store_id'               => $oldTx->store_id,
                        'notes'                  => $oldTx->notes,
                        'transactionable_id'     => $oldTx->transactionable_id,
                        'transactionable_type'   => $oldTx->transactionable_type,
                        'purchase_invoice_id'    => $oldTx->purchase_invoice_id,
                        'waste_stock_percentage' => 0, // لمنع تكرار الهدر التلقائي لأن الهدر له حركته الخاصة
                        'source_transaction_id'  => $oldTx->source_transaction_id,
                    ]);

                    $recreatedInCount++;
                    continue;
                }

                // ─── الحالة الثالثة: الحركات الخارجة اللاحقة (OUT) ───
                if ($oldTx->movement_type === InventoryTransaction::MOVEMENT_OUT) {
                    $sourceModel = null;
                    if ($oldTx->transactionable_type && $oldTx->transactionable_id && class_exists($oldTx->transactionable_type)) {
                        $sourceModel = $oldTx->transactionable_type::find($oldTx->transactionable_id);
                    }

                    $fifoService = new FifoMethodService($sourceModel);
                    $allocations = $fifoService->getAllocateFifo($productId, $oldTx->unit_id, (float) $oldTx->quantity, $storeId);

                    foreach ($allocations as $alloc) {
                        $newOutTx = InventoryTransaction::create([
                            'product_id'            => $productId,
                            'movement_type'         => InventoryTransaction::MOVEMENT_OUT,
                            'quantity'              => $alloc['deducted_qty'],
                            'unit_id'               => $alloc['target_unit_id'],
                            'package_size'          => $alloc['target_unit_package_size'],
                            'price'                 => $alloc['price_based_on_unit'],
                            'movement_date'         => $oldTx->movement_date,
                            'transaction_date'      => $oldTx->transaction_date,
                            'store_id'              => $storeId,
                            'notes'                 => $alloc['notes'] ?? $oldTx->notes,
                            'transactionable_id'    => $oldTx->transactionable_id,
                            'transactionable_type'  => $oldTx->transactionable_type,
                            'source_transaction_id' => $alloc['transaction_id'],
                        ]);

                        // في حال كان طلباً (Order)، تحديث source_transaction_id في حركة إدخال الفرع المقابلة
                        if ($oldTx->transactionable_type === Order::class) {
                            InventoryTransaction::withoutGlobalScopes()
                                ->where('transactionable_type', Order::class)
                                ->where('transactionable_id', $oldTx->transactionable_id)
                                ->where('product_id', $productId)
                                ->where('movement_type', InventoryTransaction::MOVEMENT_IN)
                                ->where('store_id', '!=', $storeId)
                                ->update([
                                    'source_transaction_id' => $alloc['transaction_id'],
                                ]);
                        }

                        // تحديث ارتباط بنود مبيعات الموزعين إن وجدت
                        DB::table('reseller_sale_items')
                            ->where('inventory_transaction_id', $oldTx->id)
                            ->update(['inventory_transaction_id' => $newOutTx->id]);
                    }

                    $recreatedOutCount++;
                }
            }

            // 7. التحقق من الرصيد النهائي
            $finalBaseBalance = (float) DB::table('inventory_transactions')
                ->where('product_id', $productId)
                ->where('store_id', $storeId)
                ->whereNull('deleted_at')
                ->selectRaw("COALESCE(SUM(
                    CASE 
                        WHEN movement_type = 'in' THEN IFNULL(base_quantity, quantity * package_size)
                        WHEN movement_type = 'out' THEN -IFNULL(base_quantity, quantity * package_size)
                        ELSE 0 
                    END
                ), 0) as balance")
                ->value('balance');

            $this->newLine();
            $this->info("📊 ─── ملخص العملية ───");
            $this->info("✔ تم إعادة إنشاء {$recreatedInCount} حركة وارد (IN) كدفعات فيفو جديدة.");
            $this->info("✔ تم إعادة تخصيص وإنشاء {$recreatedOutCount} حركة صادر (OUT) بمحرك الفيفو الصحيح.");
            $this->info("✔ الرصيد النهائي للصنف في المخزن بوحدة الأساس: {$finalBaseBalance} كجم.");

            if ($isDryRun) {
                DB::rollBack();
                $this->warn('⚠️ تم التراجع عن جميع التغييرات بنجاح لأن الأمر يعمل بوضع التجربة (--dry-run).');
            } else {
                DB::commit();
                $this->info('🎉 تم اعتماد وحفظ جميع الحركات الجديدة وتحديث الجرد بالفيفو بنجاح تام!');
            }

            return self::SUCCESS;
        } catch (Exception $e) {
            DB::rollBack();
            $this->error('❌ حدث خطأ أثناء إعادة توليد الفيفو وتم التراجع عن كافة التغييرات: ' . $e->getMessage());
            $this->line("   الملف: {$e->getFile()} (سطر {$e->getLine()})");
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
