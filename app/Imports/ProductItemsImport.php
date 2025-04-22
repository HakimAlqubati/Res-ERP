<?php

namespace App\Imports;

use App\Models\ProductItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;

class ProductItemsImport implements ToCollection
{
    public $parentProductId;
    public $importedCount = 0;
    public $failedRows = [];

    public function __construct($parentProductId)
    {
        $this->parentProductId = $parentProductId;
    }

    public function collection(Collection $rows)
    {

        try {
            DB::beginTransaction();
            foreach ($rows as $index => $row) {
                if ($row[0] && $row[1] && $row[2]) {
                    try {
                        // تأكد من وجود البيانات الأساسية
                        if (!$row[0] || !$row[1] || !$row[2]) {
                            throw new \Exception("Missing required fields in row #" . ($index + 1));
                        }
                        $unitPrice = getUnitPrice($row[0], $row[1]);
                        if(!$unitPrice) {
                            throw new \Exception("Unit price not found for product ID: " . $row[0] . " and unit ID: " . $row[1]);
                        }

                        ProductItem::create([
                            'parent_product_id' => $this->parentProductId,
                            'product_id' => $row[0],
                            'unit_id' => $row[1],
                            'quantity' => $row[2],
                            'price' => $unitPrice ?? 0,
                            'qty_waste_percentage' => $row[4] ?? 0,
                            'total_price' => $row[2] * ($unitPrice ?? 0),
                            'total_price_after_waste' => ProductItem::calculateTotalPriceAfterWaste(($row[2] * ($unitPrice ?? 0)), $row[4] ?? 0),
                            'quantity_after_waste' => ProductItem::calculateQuantityAfterWaste($row[2], $row[4] ?? 0),
                        ]);
                        $this->importedCount++;
                    } catch (\Throwable $e) {
                        Log::error("❌ Failed to import ProductItem in row #" . ($index + 1) . ": " . $e->getMessage(), [
                            'row_data' => $row->toArray(),
                            'parent_product_id' => $this->parentProductId,
                        ]);

                        $this->failedRows[] = [
                            'row_number' => $index + 1,
                            'error' => $e->getMessage(),
                            'row' => $row->toArray(),
                        ];
                    }
                }
            }
            DB::commit();
        } catch (\Throwable $e) {
            Log::error("❌ Failed to import ProductItems: " . $e->getMessage(), [
                'parent_product_id' => $this->parentProductId,
            ]);
            DB::rollBack();
        }
    }

    public function getImportedCount(): int
    {
        return $this->importedCount;
    }
    public function getFailedRows(): array
    {
        return $this->failedRows;
    }
}
