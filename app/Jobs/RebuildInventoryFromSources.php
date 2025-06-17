<?php

namespace App\Jobs;

use App\Models\InventoryTransaction;
use App\Models\PurchaseInvoice;
use App\Models\GoodsReceivedNote;
use App\Models\ProductPriceHistory;
use App\Models\StockAdjustmentDetail;
use App\Models\StockSupplyOrder;
use App\Models\UnitPrice;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Spatie\Multitenancy\Jobs\TenantAware;

class RebuildInventoryFromSources
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $productIds = [];

    public function __construct(array $productIds = [])
    {
        $this->productIds = $productIds;
    }

    public function handle(): void
    {
        DB::beginTransaction();
        Log::info('✅ Starting inventory rebuild in chronological order.');

        try {
            ProductPriceHistory::truncate();
            $this->updateAllUnitPricesFromExcelImports(); // ← سنبني هذه الدالة أدناه

            $records = collect();

            $adjustmentDetails = \App\Models\StockAdjustmentDetail::where('adjustment_type', StockAdjustmentDetail::ADJUSTMENT_TYPE_INCREASE)
                ->whereNull('deleted_at')
                ->with('store') // إذا كنت تحتاج اسم المخزن في notes
                ->get();

            foreach ($adjustmentDetails as $detail) {
                $records->push([
                    'date' => $detail->adjustment_date,
                    'type' => 'stock_adjustment_detail',
                    'model' => $detail,
                ]);
            }
            // 🟩 جمع GRNs
            $grns = GoodsReceivedNote::approved()->with(['grnDetails', 'store'])->get();
            foreach ($grns as $grn) {
                $records->push([
                    'date' => $grn->grn_date,
                    'type' => 'grn',
                    'model' => $grn,
                ]);
            }

            // 🟦 جمع فواتير الشراء
            $invoices = PurchaseInvoice::with(['details', 'store'])->get();
            foreach ($invoices as $invoice) {
                $hasGRN = GoodsReceivedNote::where('purchase_invoice_id', $invoice->id)->exists();
                if ($hasGRN) {
                    continue;
                }
                $records->push([
                    'date' => $invoice->date,
                    'type' => 'purchase_invoice',
                    'model' => $invoice,
                ]);
            }

            // 🟧 جمع أوامر التوريد
            $supplyOrders = StockSupplyOrder::with(['details', 'store'])->get();
            foreach ($supplyOrders as $order) {
                $records->push([
                    'date' => $order->order_date,
                    'type' => 'stock_supply',
                    'model' => $order,
                ]);
            }

            // ✅ الترتيب حسب التاريخ
            $sortedRecords = $records->sortBy('date');

            // ⏱️ تنفيذ كل سجل حسب نوعه
            foreach ($sortedRecords as $item) {
                match ($item['type']) {
                    'grn' => $this->createFromGRN($item['model']),
                    'purchase_invoice' => $this->createFromInvoice($item['model']),
                    'stock_supply' => $this->createFromSupplyOrder($item['model']),
                    'stock_adjustment_detail' => $this->createFromStockAdjustmentDetail($item['model']),
                };
            }

            DB::commit();
            Log::info('✅ Inventory rebuilt successfully in chronological order.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Failed to rebuild inventory: ' . $e->getMessage());
        }
    }




    protected function createFromInvoice(PurchaseInvoice $invoice): void
    {
        foreach ($invoice->details as $detail) {
            // if (!empty($this->productIds) && !in_array($detail->product_id, $this->productIds)) {
            //     continue;
            // }
            $notes = 'Purchase invoice with ID ' . $detail->purchase_invoice_id
                . ' in (' . $invoice->store?->name . ')';

            InventoryTransaction::create([
                'product_id' => $detail->product_id,
                'movement_type' => InventoryTransaction::MOVEMENT_IN,
                'quantity' => $detail->quantity,
                'unit_id' => $detail->unit_id,
                'package_size' => $detail->package_size,
                'price' => $detail->price,
                'movement_date' => $invoice->date,
                'transaction_date' => $invoice->date,
                'store_id' => $invoice->store_id,
                'notes' => $notes,
                'transactionable_id' => $invoice->id,
                'transactionable_type' => PurchaseInvoice::class,
                'waste_stock_percentage' => $detail->waste_stock_percentage,
            ]);
        }
    }

    protected function createFromGRN(GoodsReceivedNote $grn): void
    {
        foreach ($grn->grnDetails as $detail) {
            // if (!empty($this->productIds) && !in_array($detail->product_id, $this->productIds)) {
            //     continue;
            // }
            $notes = 'GRN with ID ' . $grn->id;
            if ($grn->store?->name) {
                $notes .= ' in (' . $grn->store->name . ')';
            }

            $priceInfo = $this->getLastPurchasePrice(
                $detail->product_id,
                $grn->store_id,
                $grn->grn_date
            );

            $price = $priceInfo
                ? $priceInfo['unit_price'] * $detail->package_size
                : 0;

            InventoryTransaction::create([
                'product_id' => $detail->product_id,
                'movement_type' => InventoryTransaction::MOVEMENT_IN,
                'quantity' => $detail->quantity,
                'unit_id' => $detail->unit_id,
                'package_size' => $detail->package_size,
                // 'price' => getUnitPrice($detail->product_id, $detail->unit_id) ?? 0,
                'price' => $price,
                'movement_date' => $grn->grn_date,
                'transaction_date' => $grn->grn_date,
                'store_id' => $grn->store_id,
                'notes' => $notes,
                'transactionable_id' => $grn->id,
                'transactionable_type' => GoodsReceivedNote::class,
            ]);
        }
    }

    protected function createFromSupplyOrder(StockSupplyOrder $order): void
    {
        foreach ($order->details as $detail) {
            // if (!empty($this->productIds) && !in_array($detail->product_id, $this->productIds)) {
            //     continue;
            // }
            $notes = 'Stock supply with ID ' . $detail->stock_supply_order_id
                . ' in (' . $order->store?->name . ')';

            $priceInfo = $this->getLastPurchasePrice(
                $detail->product_id,
                1,
                $order->order_date
            );

            $price = $priceInfo
                ? $priceInfo['unit_price'] * $detail->package_size
                : 0;

            InventoryTransaction::create([
                'product_id' => $detail->product_id,
                'movement_type' => InventoryTransaction::MOVEMENT_IN,
                'quantity' => $detail->quantity,
                'unit_id' => $detail->unit_id,
                'package_size' => $detail->package_size,
                // 'price' => $detail->price,
                'price' => $price,
                'movement_date' => $order->order_date,
                'transaction_date' => $order->order_date,
                'store_id' => $order->store_id,
                'notes' => $notes,
                'transactionable_id' => $order->id,
                'transactionable_type' => StockSupplyOrder::class,
                'waste_stock_percentage' => $detail->waste_stock_percentage,
            ]);
        }
    }

    protected function createFromStockAdjustmentDetail(\App\Models\StockAdjustmentDetail $detail): void
    {
        // if (!empty($this->productIds) && !in_array($detail->product_id, $this->productIds)) {
        //     return;
        // }

        $notes = 'Stock adjustment';
        if ($detail->store?->name) {
            $notes .= ' in (' . $detail->store->name . ')';
        }
        $priceInfo = $this->getLastPurchasePrice(
            $detail->product_id,
            $detail->store_id,
            $detail->adjustment_date
        );

        $price = $priceInfo
            ? $priceInfo['unit_price'] * $detail->package_size
            : 0;
        InventoryTransaction::create([
            'product_id' => $detail->product_id,
            'movement_type' => InventoryTransaction::MOVEMENT_IN,
            'quantity' => $detail->quantity,
            'unit_id' => $detail->unit_id,
            'package_size' => $detail->package_size,
            'price' => $price,
            // 'price' => getUnitPrice($detail->product_id, $detail->unit_id) ?? 0,
            'movement_date' => $detail->adjustment_date,
            'transaction_date' => $detail->adjustment_date,
            'store_id' => $detail->store_id,
            'notes' => $notes,
            'transactionable_id' => $detail->id,
            'transactionable_type' => \App\Models\StockAdjustmentDetail::class,
        ]);
    }


    public function updateUnitPricesFromExcelImport(int $productId): void
    {
        $transactions = InventoryTransaction::where('product_id', $productId)
            ->where('transactionable_type', 'ExcelImport')
            ->whereNull('deleted_at')
            ->get();

        $unitPrices = UnitPrice::where('product_id', $productId)
            ->get()
            ->keyBy('unit_id');

        foreach ($transactions as $transaction) {
            if ($transaction->package_size == 0) {
                // لتجنب القسمة على صفر
                continue;
            }
            $pricePerPiece = $transaction->price / $transaction->package_size;

            foreach ($unitPrices as $unitId => $unitPrice) {
                $newPrice = $pricePerPiece * $unitPrice->package_size;

                // if (round($unitPrice->price, 4) === round($newPrice, 4)) {
                //     continue;
                // }

                // تخزين سجل السعر
                ProductPriceHistory::create([
                    'product_id'       => $productId,
                    'product_item_id'  => null, // عدله لو عندك
                    'unit_id'          => $unitId,
                    'old_price'        => 0,
                    'new_price'        => $newPrice,
                    'source_type'      => 'ExcelImport',
                    'source_id'        => $transaction->transactionable_id,
                    'note'             => 'Imported from ExcelImport transaction',
                    'date'             => $transaction->transaction_date,
                ]);

                // تحديث السعر الفعلي في وحدة المنتج
                $unitPrice->update([
                    'price' => $newPrice,
                ]);
            }
        }
    }

    protected function updateAllUnitPricesFromExcelImports(): void
    {
        $productIds = InventoryTransaction::where('transactionable_type', 'ExcelImport')
            ->whereNull('deleted_at')
            ->distinct()
            ->pluck('product_id');

        foreach ($productIds as $productId) {
            $this->updateUnitPricesFromExcelImport($productId);
        }
    }

    function getLastPurchasePrice(int $productId, int $storeId, string|\DateTimeInterface $date): ?array
    {
        $invoice = \App\Models\PurchaseInvoice::query()
            ->where('store_id', $storeId)
            ->whereDate('date', '<=', $date)
            ->whereHas('details', function ($q) use ($productId) {
                $q->where('product_id', $productId);
            })
            ->with(['details' => function ($q) use ($productId) {
                $q->where('product_id', $productId)
                    ->orderByDesc('id'); // الأحدث أولاً
            }])
            ->orderByDesc('date')
            ->first();

        $detail = $invoice?->details->first();

        if (!$detail || $detail->package_size == 0) {
            return null;
        }

        return [
            'unit_price' => $detail->price / $detail->package_size, // ← سعر القطعة الواحدة
            'source_invoice_id' => $invoice->id ?? null,
        ];
    }
}
