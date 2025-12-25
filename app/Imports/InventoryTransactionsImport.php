<?php

namespace App\Imports;

use App\Models\Branch;
use Throwable;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\Unit;
use App\Models\UnitPrice;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class InventoryTransactionsImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                $productId = $row['id'];
                $unitName = trim($row['unit']);
                $quantity = $row['qty'];

                if (empty($quantity)) {
                    continue;
                }

                $unit = Unit::where('name', $unitName)->first();
                $product = Product::find($productId);

                if (!$product || !$unit) {
                    continue;
                }

                $category = $product->category;
                $storeId = 1; // default

                if ($category) {

                    $customizedBranch = Branch::whereHas(
                        'categories',
                        fn($q) =>
                        $q->where('categories.id', $category->id)
                    )->first();


                    if (($customizedBranch && $category->is_manafacturing) || ($customizedBranch)) {
                        $storeId = $customizedBranch->store_id;
                    } else if ($category->is_manafacturing && !$customizedBranch) {
                        $storeId = 8; // fallback for manufacturing category not customized
                    }
                }

                $unitPrice = UnitPrice::where('product_id', $productId)
                    ->where('unit_id', $unit->id)
                    ->first();

                InventoryTransaction::create([
                    'product_id' => $product->id,
                    'movement_type' => InventoryTransaction::MOVEMENT_IN,
                    'quantity' => $quantity,
                    'unit_id' => $unit->id,
                    'movement_date' => now(),
                    'package_size' => $unitPrice->package_size ?? 1,
                    'store_id' => $storeId,
                    'price' => $unitPrice->price ?? 0,
                    'transaction_date' => now(),
                    'notes' => 'Opening Stock of Import from Excel in ' . now()->format('Y-m-d'),
                    'transactionable_id' => 0,
                    'transactionable_type' => 'ExcelImport',
                ]);
            }
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
        }
    }
}
