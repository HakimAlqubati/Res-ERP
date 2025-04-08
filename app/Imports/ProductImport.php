<?php

namespace App\Imports;

use App\Models\Category;
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
                    'minimum_stock_qty' => 0,
                ]);
                UnitPrice::create([
                    'product_id' => $product->id,
                    'unit_id' => $unit->id,
                    'price' => $price,
                    'package_size' => $packageSize,

                ]);
            } else {
                UnitPrice::create([
                    'product_id' => $product->id,
                    'unit_id' => $unit->id,
                    'price' => $price,
                    'package_size' => $packageSize,
                ]);
            }



            $this->successCount++;
            // DB::commit();
        } catch (\Throwable $e) {
            Log::channel('single')->error('❌ Import Error', [
                'row' => $row,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // DB::rollBack();
            // Log::error("Failed to import row: " . json_encode($row) . ' - ' . $e->getMessage());
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
        ];
    }

    public function headingRow(): int
    {
        return 1;
    }

    public function getSuccessfulImportsCount(): int
    {
        return $this->successCount;
    }
}
