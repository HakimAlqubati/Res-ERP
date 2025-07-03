<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ResellerSaleItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'reseller_sale_id',
        'product_id',
        'unit_id',
        'package_size',
        'quantity',
        'unit_price',
        'total_price',
        'inventory_transaction_id',
    ];

    public function sale()
    {
        return $this->belongsTo(ResellerSale::class, 'reseller_sale_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function inventoryTransaction()
    {
        return $this->belongsTo(InventoryTransaction::class);
    }
    protected static function booted()
    {
        static::saving(function ($item) {
            $item->total_price = $item->unit_price * $item->quantity;
        });


        static::created(function (ResellerSaleItem $item) {
            $sale = $item->sale;

            if (!$sale || !$sale->store_id) {
                return;
            }

            DB::transaction(function () use ($item, $sale) {
                DB::transaction(function () use ($item, $sale) {
                    // 1. استدعاء خدمة FIFO
                    $fifoService = new \App\Services\FifoMethodService($sale);

                    $allocations = $fifoService->getAllocateFifo(
                        $item->product_id,
                        $item->unit_id,
                        $item->quantity,
                        $sale->store_id

                    );
                    Log::info('fofo__', [$allocations]);
                    if (empty($allocations)) {
                        throw new \Exception("FIFO allocation failed: No available stock to fulfill the requested quantity.");
                    }


                    // 2. تسجيل الحركات بناءً على التخصيصات
                    self::moveFromInventory($allocations, $item);
                });
            });
        });
    }

    public static function moveFromInventory(array $allocations, ResellerSaleItem $item)
    {
        $sale = $item->sale;

        foreach ($allocations as $alloc) {
            \App\Models\InventoryTransaction::create([
                'product_id'           => $item->product_id,
                'movement_type'        => \App\Models\InventoryTransaction::MOVEMENT_OUT,
                'quantity'             => $alloc['deducted_qty'],
                'unit_id'              => $alloc['target_unit_id'],
                'package_size'         => $alloc['target_unit_package_size'],
                'price'                => $alloc['price_based_on_unit'],
                'movement_date'        => $sale->sale_date ?? now(),
                'transaction_date'     => $sale->sale_date ?? now(),
                'store_id'             => $alloc['store_id'],
                'notes'                => 'Reseller Sale #' . $sale->id,

                'transactionable_id'   => $sale->id,
                'transactionable_type' => \App\Models\ResellerSale::class,
                'source_transaction_id' => $alloc['transaction_id'],
            ]);

            // اختياري: يمكنك ربط الـ InventoryTransaction بالعنصر إن أردت
            $item->inventory_transaction_id = $item->inventory_transaction_id ?? null; // لو عندك عمود لهذا
        }
    }
}