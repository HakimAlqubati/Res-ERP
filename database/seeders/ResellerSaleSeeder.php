<?php

namespace Database\Seeders;

use App\Models\ResellerSale;
use App\Models\ResellerSaleItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ResellerSaleSeeder extends Seeder
{
    public function run(): void
    {
        $storeId = 1;
        $productIds = [1, 2, 3];
        $unitId = 1;
        $createdBy = 128;

        DB::transaction(function () use ($storeId, $productIds, $unitId, $createdBy) {
            for ($i = 1; $i <= 10; $i++) {
                $sale = ResellerSale::create([
                    'branch_id'    => 22,
                    'store_id'     => $storeId,
                    'sale_date'    => Carbon::now()->subDays($i),
                    'total_amount' => 0,
                    'note'         => 'Test sale #' . $i,
                    'created_by'   => $createdBy,
                ]);

                foreach ($productIds as $productId) {
                    $quantity   = rand(1, 5);
                    $unitPrice  = rand(10, 50);

                    ResellerSaleItem::create([
                        'reseller_sale_id' => $sale->id,
                        'product_id'       => $productId,
                        'unit_id'          => $unitId,
                        'package_size'     => 1,
                        'quantity'         => $quantity,
                        'unit_price'       => $unitPrice,
                        'total_price'      => $quantity * $unitPrice,
                    ]);
                }

                $sale->updateTotalAmount();
            }
        });
    }
}