<?php
// app/Services/ProductPriceHistoryService.php

namespace App\Services;

use App\Models\InventoryTransaction;
use App\Models\GoodsReceivedNote;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceDetail;
use App\Models\UnitPrice;
use Google\Service\AndroidPublisher\Resource\Purchases;

class ProductPriceHistoryService
{

    public function getPriceHistory($productId = null)
    {
        $query = InventoryTransaction::with(['product', 'unit'])
            ->where('movement_type', InventoryTransaction::MOVEMENT_IN)
            ->where(function ($q) {
                $q
                    ->where('transactionable_type', GoodsReceivedNote::class)
                    ->orWhere('transactionable_type', PurchaseInvoice::class)
                    ->orWhere('transactionable_type', 'ExcelImport');
            })
            ->orderBy('product_id')
            ->orderBy('created_at');

        if ($productId) {
            $query->where('product_id', $productId);
        }
        $result = $query->get();

        return $result->map(function ($tx) {
            $sourceType = class_basename($tx->transactionable_type);
            $hasPurchaseInvoice = false;
            $purchaseInvoiceId = null;
            $actualPrice = $tx->price;

            if ($sourceType === 'GoodsReceivedNote') {
                $grn = \App\Models\GoodsReceivedNote::with('purchaseInvoice')->find($tx->transactionable_id);
                $hasPurchaseInvoice = $grn?->purchase_invoice_id !== null;
                if ($hasPurchaseInvoice) {
                    $purchaseInvoiceId = $grn->purchase_invoice_id;

                    // 🔁 جلب السعر من الفاتورة المرتبطة
                    $detail = \App\Models\PurchaseInvoiceDetail::where('purchase_invoice_id', $purchaseInvoiceId)
                        ->where('product_id', $tx->product_id)
                        ->where('unit_id', $tx->unit_id)
                        ->where('package_size', $tx->package_size)
                        ->first();

                    if ($detail) {
                        $actualPrice = $detail->price;
                    }
                }
            }

            if ($sourceType === 'PurchaseInvoice') {
                $detail = PurchaseInvoiceDetail::where('purchase_invoice_id', $tx->transactionable_id)
                    ->where('product_id', $tx->product_id)
                    ->where('unit_id', $tx->unit_id)
                    ->where('package_size', $tx->package_size)
                    ->first();

                if ($detail) {
                    $actualPrice = $detail->price;
                }
            }

            // 🧠 حساب سعر الوحدة الصغير:
            $baseUnitPrice = $tx->package_size > 0 ? $actualPrice / $tx->package_size : null;

            // 🧾 أسعار كل الوحدات الأخرى المحفوظة
            $unitPrices = UnitPrice::with('unit')
                ->where('product_id', $tx->product_id)
                ->get()
                ->map(function ($up) use ($baseUnitPrice) {
                    return [
                        'unit' => $up->unit->name ?? '',
                        'unit_id' => $up->unit_id,
                        'package_size' => $up->package_size,
                        'converted_price' => $baseUnitPrice ? round($baseUnitPrice * $up->package_size, 4) : null,
                    ];
                });
            return [
                'product_id'    => $tx->product_id,
                'product'       => $tx->product->name ?? '',
                'product_code'  => $tx->product->code ?? '',
                'unit'          => $tx->unit->name ?? '',
                'package_size'  => $tx->package_size,
                'price'         => $actualPrice,
                'date'          => $tx->created_at->format('Y-m-d'),
                'source_type'   => $sourceType,
                'source_id'     => $tx->transactionable_id,
                'has_purchase_invoice' => $hasPurchaseInvoice,
                'purchase_invoice_id' => $purchaseInvoiceId,
                'converted_unit_prices' => $unitPrices,
            ];
        });
    }
    public function getPastOutTransactions($productId)
    {
        return InventoryTransaction::query()
            ->where('movement_type', InventoryTransaction::MOVEMENT_OUT)
            ->where('product_id', $productId)
            ->orderBy('created_at', 'desc')
            ->first()
        ;
    }
}
