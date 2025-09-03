<?php

namespace App\Imports;

use Exception;
use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\Unit;
use App\Models\UnitPrice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class ProductImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use SkipsFailures;

    private int $successCount = 0;


    public function model(array $row)
    {
        // DB::beginTransaction();
        try {
            $packageSize = $row['qty_per_pack'];
            $productId = (int) $row['id'];
            $productName = trim($row['product_name'] ?? '');
            $categoryName = trim($row['category'] ?? '');
            $unitName = trim($row['unit'] ?? '');
            $price = (float) ($row['price'] ?? 0);
            $minimumStockQty = (int) ($row['minimum_stock_qty'] ?? 0);
            $stockQty = (float) ($row['stock_qty'] ?? 0);

            if (!$productId || !$productName || !$categoryName || !$unitName || $price <= 0) {
                return null;
            }

            $category = Category::where('name', $categoryName)->first();
            $unit = Unit::where('name', $unitName)->first();

            if (!$category || !$unit) {
                return null;
            }

            // إما نجد المنتج أو ننشئه حسب ID
            $product = Product::find($productId);
            if (!$product) {
                $product = Product::create([
                    'id' => $productId,
                    'name' => $productName,
                    'code' =>  Product::generateProductCode($category->id),
                    'description' => '',
                    'active' => true,
                    'category_id' => $category->id,
                    'minimum_stock_qty' => $minimumStockQty,

                ]);
                UnitPrice::create([
                    'product_id' => $product->id,
                    'unit_id' => $unit->id,
                    'price' => $price,
                    'package_size' => $packageSize,
                    'order' => $packageSize,

                ]);
            }
            if ($product) {
                $existingUnitPrice = UnitPrice::where('product_id', $product->id)->first();

                $calculatedPrice = $price;

                if ($existingUnitPrice) {
                    $basePrice = $existingUnitPrice->price;
                    $calculatedPrice = $packageSize * $basePrice;
                }

                $unitPriceExists = UnitPrice::where('product_id', $product->id)
                    ->where('unit_id', $unit->id)
                    ->first();

                if (!$unitPriceExists) {
                    UnitPrice::create([
                        'product_id' => $product->id,
                        'unit_id' => $unit->id,
                        'price' => $calculatedPrice,
                        'package_size' => $packageSize,
                        'order' => $packageSize,
                    ]);
                } else {
                    // Update existing unit price if needed
                    $unitPriceExists->update([
                        'price' => $calculatedPrice,
                        'package_size' => $packageSize,
                        'order' => $packageSize,
                    ]);
                }
                // Queue product for stock addition if needed
                if ($stockQty > 0) {
                    InventoryTransaction::create([
                        'product_id' => $product->id,
                        'movement_type' => InventoryTransaction::MOVEMENT_IN,
                        'quantity' => $stockQty,
                        'unit_id' => $unit->id,
                        'movement_date' => now(),
                        'package_size' => $packageSize,
                        'price' => $price,
                        'transaction_date' => now(),
                        'notes' => 'Opening stock from import',
                        'transactionable_id' => $product->id,
                        'store_id' => 1,
                        'transactionable_type' => 'ProductImport',
                        'waste_stock_percentage' => 0,
                    ]);
                }
            }



            $this->successCount++;
            // DB::commit();
        } catch (Exception $e) {
            Log::channel('single')->error('❌ Import Error', [
                'row' => $row,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // DB::rollBack();
            Log::error("Failed to import row: " . json_encode($row) . ' - ' . $e->getMessage());
        }

        return null; // we're handling manually
    }

    public function rules(): array
    {
        return [
            'id' => 'required|integer',
            'product_name' => 'required|string',
            'category' => 'required|string',
            'unit' => 'required|string',
            'price' => 'required|numeric|min:0.01',
            'stock_qty' => 'nullable|numeric|min:0',
            'qty_per_pack' => 'nullable|numeric|min:1',
        ];
    }


    public function headingRow(): int
    {
        return 1;
    }
    public function batchSize(): int
    {
        return 500;
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function getSuccessfulImportsCount(): int
    {
        return $this->successCount;
    }
    private function resolvePackageSizeForPrice(int $productId, int $unitId, int $packageSize, float $price): int
    {
        $final = $packageSize;

        // هل هناك سعر(أسعار) بنفس الـ package_size لهذا المنتج/الوحدة؟
        $conflicts = UnitPrice::where('product_id', $productId)
            ->where('unit_id', $unitId)
            ->where('package_size', $packageSize)
            ->get();

        if ($conflicts->isEmpty()) {
            return $final; // لا تعارض
        }

        // توجد قيود بنفس الـ package_size
        // إذا كان سعري أعلى من أي الموجودين، أرفع الـ package_size بمقدار 1
        $maxExistingPrice = (float) $conflicts->max('price');
        if ($price > $maxExistingPrice) {
            $final = $packageSize + 1;
        } else {
            // إن لم يكن أعلى، نبقيه كما هو
            $final = $packageSize;
        }

        // تأكد أن الـ package_size الجديد غير مستخدم لسعر مختلف
        // (نكرر الزيادة حتى نجد خانة فاضية أو نفس السعر)
        while (
            UnitPrice::where('product_id', $productId)
            ->where('unit_id', $unitId)
            ->where('package_size', $final)
            ->where(function ($q) use ($price) {
                $q->where('price', '!=', $price);
            })
            ->exists()
        ) {
            $final++;
        }

        return $final;
    }
}
