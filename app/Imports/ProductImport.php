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
use Maatwebsite\Excel\Validators\Failure;

class ProductImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use SkipsFailures {
        onFailure as parentOnFailure;
    }

    private int $successCount = 0;

    public function onFailure(Failure ...$failures)
    {
        $this->parentOnFailure(...$failures);

        foreach ($failures as $failure) {
            Log::warning("ProductImport [Row {$failure->row()}] Validation Error on field '{$failure->attribute()}': " . implode(', ', $failure->errors()), [
                'values' => $failure->values(),
            ]);
        }
    }

    public function model(array $row)
    {
        // DB::beginTransaction();
        try {
            $packageSize = (int) ($row['qty_per_pack'] ?? 1);
            $productId = (int) ($row['id'] ?? 0);
            $productName = trim($row['product_name'] ?? '');
            $categoryName = trim($row['category'] ?? '');
            $codeOldSystem = trim($row['code_old_system'] ?? '');
            $unitName = trim($row['unit'] ?? '');
            $unitPerPackName = trim($row['unit_per_pack'] ?? '');
            $price = (float) ($row['price'] ?? 0);
            $minimumStockQty = (int) ($row['minimum_stock_qty'] ?? 0);
            $stockQty = (float) ($row['stock_qty'] ?? 0);

            if (!$productId || !$productName || !$categoryName || !$unitName || $price <= 0) {
                Log::warning("ProductImport skipped row: Missing required fields or invalid price.", [
                    'id' => $productId,
                    'product_name' => $productName,
                    'category' => $categoryName,
                    'unit' => $unitName,
                    'price' => $price,
                    'row' => $row,
                ]);
                return null;
            }

            $category = Category::where('name', $categoryName)->first();
            if (!$category) {
                Log::warning("ProductImport skipped row (ID: {$productId}): Category '{$categoryName}' not found in database.", [
                    'row' => $row,
                ]);
                return null;
            }

            $unit = Unit::where('name', $unitName)->first();
            if (!$unit) {
                Log::warning("ProductImport skipped row (ID: {$productId}): Unit '{$unitName}' not found in database.", [
                    'row' => $row,
                ]);
                return null;
            }

            $unitPerPack = null;
            if ($packageSize > 1 && $unitPerPackName) {
                $unitPerPack = Unit::where('name', $unitPerPackName)->first();
                if (!$unitPerPack) {
                    Log::warning("ProductImport skipped row (ID: {$productId}): Unit Per Pack '{$unitPerPackName}' not found in database.", [
                        'row' => $row,
                    ]);
                    return null;
                }
            }

            // إما نجد المنتج أو ننشئه حسب ID
            $product = Product::find($productId);
            if (!$product) {
                $product = Product::create([
                    'id' => $productId,
                    'name' => $productName,
                    'code_old_system' => $codeOldSystem,
                    'code' => \App\Models\Setting::getSetting('product_code_generation_method', \App\Enums\ProductCodeGenerationMethod::AUTO->value) === \App\Enums\ProductCodeGenerationMethod::AUTO->value 
                        ? Product::generateProductCode($category->id) 
                        : (trim($row['code'] ?? '') ?: null),
                    'description' => '',
                    'active' => true,
                    'category_id' => $category->id,
                    'minimum_stock_qty' => $minimumStockQty,
                ]);
            }

            if ($product) {
                $packPrice = $price;
                $unitPriceExists = UnitPrice::where('product_id', $product->id)
                    ->where('unit_id', $unit->id)
                    ->first();

                if (!$unitPriceExists) {
                    UnitPrice::create([
                        'product_id' => $product->id,
                        'unit_id' => $unit->id,
                        'price' => $packPrice,
                        'package_size' => $packageSize,
                        'order' => $packageSize,
                    ]);
                } else {
                    // Update existing unit price if needed
                    $unitPriceExists->update([
                        'price' => $packPrice,
                        'package_size' => $packageSize,
                        'order' => $packageSize,
                    ]);
                }

                // If packageSize > 1, create/update a piece unit price
                if ($packageSize > 1 && $unitPerPack) {
                    $piecePrice = $packPrice / $packageSize;
                    $pieceUnitPriceExists = UnitPrice::where('product_id', $product->id)
                        ->where('unit_id', $unitPerPack->id)
                        ->first();
                    
                    if (!$pieceUnitPriceExists) {
                        UnitPrice::create([
                            'product_id' => $product->id,
                            'unit_id' => $unitPerPack->id,
                            'price' => $piecePrice,
                            'package_size' => 1,
                            'order' => 1,
                        ]);
                    } else {
                        $pieceUnitPriceExists->update([
                            'price' => $piecePrice,
                            'package_size' => 1,
                            'order' => 1,
                        ]);
                    }
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
                        'price' => $packPrice,
                        'transaction_date' => now(),
                        'notes' => 'Opening stock from import',
                        'transactionable_id' => $product->id,
                        'store_id' => $row['store'],
                        'transactionable_type' => 'ProductImport',
                        'waste_stock_percentage' => 0,
                    ]);
                }
            }



            $this->successCount++;
            // DB::commit();
        } catch (Exception $e) {
            Log::error("ProductImport exception on row (ID: " . ($row['id'] ?? 'unknown') . "): " . $e->getMessage(), [
                'row' => $row,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
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
            'unit_per_pack' => 'nullable|string',
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
