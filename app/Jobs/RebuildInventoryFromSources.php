<?php

namespace App\Jobs;

use App\Models\InventoryTransaction;
use App\Models\PurchaseInvoice;
use App\Models\GoodsReceivedNote;
use App\Models\StockSupplyOrder;
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

    public function handle(): void
    {
        DB::beginTransaction();
        Log::info('✅ Starting inventory rebuild in chronological order.');

        try {
            $records = collect();

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
                };
            }

            DB::commit();
            Log::info('✅ Inventory rebuilt successfully in chronological order.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Failed to rebuild inventory: ' . $e->getMessage());
        }
    }

    protected function rebuildFromPurchaseInvoices(): void
    {
        $invoices = PurchaseInvoice::with(['details', 'store'])->get();


        foreach ($invoices as $invoice) {
            // if ($invoice->grn && $invoice->grn->has_inventory_transaction) {
            $hasGRN = GoodsReceivedNote::where('purchase_invoice_id', $invoice->id)->exists();
            if ($hasGRN) {
                continue;
            }
            foreach ($invoice->details as $detail) {
                $notes = 'Purchase invoice with id ' . $detail->purchase_invoice_id
                    .   ' in (' . $detail->purchaseInvoice->store->name . ')';

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
    }

    protected function rebuildFromGRNs(): void
    {
        $grns = GoodsReceivedNote::approved()->with(['grnDetails', 'store'])->get();

        foreach ($grns as $grn) {
            foreach ($grn->grnDetails as $detail) {
                $notes = 'GRN with id ' . $grn->id;
                if ($grn->store?->name) {
                    $notes .= ' in (' . $grn->store->name . ')';
                }

                InventoryTransaction::create([
                    'product_id' => $detail->product_id,
                    'movement_type' => InventoryTransaction::MOVEMENT_IN,
                    'quantity' => $detail->quantity,
                    'unit_id' => $detail->unit_id,
                    'package_size' => $detail->package_size,
                    'price' => getUnitPrice($detail->product_id, $detail->unit_id),
                    'movement_date' => $grn->grn_date,
                    'transaction_date' => $grn->grn_date,
                    'store_id' => $grn->store_id,
                    'notes' => $notes,
                    'transactionable_id' => $grn->id,
                    'transactionable_type' => GoodsReceivedNote::class,
                ]);
            }
        }
    }

    protected function rebuildFromStockSupplyOrders(): void
    {
        $orders = StockSupplyOrder::with('details', 'store')->get();

        foreach ($orders as $order) {
            foreach ($order->details as $detail) {
                $notes = 'Stock supply with ID ' . $detail->stock_supply_order_id
                    . ' in (' . $detail->order->store->name . ')';

                InventoryTransaction::create([
                    'product_id' => $detail->product_id,
                    'movement_type' => InventoryTransaction::MOVEMENT_IN,
                    'quantity' => $detail->quantity,
                    'unit_id' => $detail->unit_id,
                    'package_size' => $detail->package_size,
                    'price' => $detail->price,
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
    }



    protected function createFromInvoice(PurchaseInvoice $invoice): void
    {
        foreach ($invoice->details as $detail) {
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
            $notes = 'GRN with ID ' . $grn->id;
            if ($grn->store?->name) {
                $notes .= ' in (' . $grn->store->name . ')';
            }

            InventoryTransaction::create([
                'product_id' => $detail->product_id,
                'movement_type' => InventoryTransaction::MOVEMENT_IN,
                'quantity' => $detail->quantity,
                'unit_id' => $detail->unit_id,
                'package_size' => $detail->package_size,
                'price' => getUnitPrice($detail->product_id, $detail->unit_id) ?? 0,
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
            $notes = 'Stock supply with ID ' . $detail->stock_supply_order_id
                . ' in (' . $order->store?->name . ')';

            InventoryTransaction::create([
                'product_id' => $detail->product_id,
                'movement_type' => InventoryTransaction::MOVEMENT_IN,
                'quantity' => $detail->quantity,
                'unit_id' => $detail->unit_id,
                'package_size' => $detail->package_size,
                'price' => $detail->price,
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
}
