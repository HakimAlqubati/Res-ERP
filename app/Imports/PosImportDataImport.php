<?php

namespace App\Imports;

use App\Models\Branch;
use App\Models\PosImportData;
use App\Models\PosImportDataDetail;
use App\Models\Product;
use App\Models\Unit;
use App\Models\PosSale;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB; // <<< مهم للـ transaction
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Events\AfterImport;

class PosImportDataImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure, WithEvents
{
    use SkipsFailures;

    /** @var PosImportData */
    protected $header;

    /** @var int */
    protected int $successCount = 0;

    public function __construct(
        protected int $branchId,
        protected int $createdBy,
        protected string $date,
        protected ?string $notes = null,
        protected ?int $defaultUnitId = null,
    ) {
        // ننشئ رأس الاستيراد مرة واحدة
        $this->header = PosImportData::create([
            'date'       => $this->date,
            'branch_id'  => $this->branchId,
            'created_by' => $this->createdBy,
            'notes'      => $this->notes,
        ]);
    }

    /**
     * يُستدعى لكل صف في الـ Excel
     */
    public function model(array $row)
    {
        try {
            $name  = $this->firstFilled($row, ['product', 'item', 'name', 'product_name']);
            $qty   = $this->firstFilled($row, ['qty', 'quantity', 'qty_total', 'grand_total_qty']);
            $unitN = $this->firstFilled($row, ['unit', 'uom']);

            if (!$name || (!is_numeric($qty) && $qty !== 0 && $qty !== '0')) {
                return null;
            }

            $qty = (float) $qty;
            if ($qty == 0.0) {
                return null;
            }

            $product = Product::where('name', trim($name))->first();

            if (!$product) {
                Log::warning('POS Import: product not found', ['name' => $name]);
                return null;
            }

            $unitId = $this->resolveUnitId($unitN, $product);

            if (!$unitId) {
                Log::warning('POS Import: unit not resolved', ['product' => $product->id, 'unit' => $unitN]);
                return null;
            }

            PosImportDataDetail::create([
                'pos_import_data_id' => $this->header->id,
                'product_id'         => $product->id,
                'unit_id'            => $unitId,
                'quantity'           => $qty,
            ]);

            $this->successCount++;
        } catch (\Throwable $e) {
            Log::error('POS Import row failed', [
                'row'     => $row,
                'message' => $e->getMessage(),
            ]);
        }

        return null;
    }

    public function rules(): array
    {
        return [
            '*.qty'      => 'nullable|numeric',
            '*.quantity' => 'nullable|numeric',
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

    // ================== أحداث الاستيراد ==================

    public function registerEvents(): array
    {
        return [
            AfterImport::class => function (AfterImport $event) {
                $this->createPosSaleFromImportHeader();
            },
        ];
    }

    /**
     * ينشئ سند PosSale + البنود من تفاصيل الاستيراد،
     * ثم ينشئ حركات المخزون من البنود.
     */
    protected function createPosSaleFromImportHeader(): void
    {
        try {
            $header = $this->header;

            if (! $header) {
                return;
            }

            // جلب الفرع + المخزن المرتبط به
            /** @var Branch|null $branch */
            $branch = Branch::find($header->branch_id);
            if (! $branch) {
                return;
            }

            $storeId = $branch->store_id;
             if (is_null($storeId)) {
                // لا يوجد مخزن مربوط بهذا الفرع
                return;
            }

            // نحمل العلاقات مرة واحدة
            $header->loadMissing([
                'details.product.unitPrices', // يفترض أن PosImportDataDetail لديه علاقة product()، و Product لديه unitPrices()
            ]);

            // لو لا يوجد تفاصيل، لا نكمل
            if ($header->details->isEmpty()) {
                return;
            }
            Log::info('mmm', [$header->details]);

            // نعمل كل شيء داخل Transaction
            $sale = DB::transaction(function () use ($header, $storeId): ?PosSale {

                // 1) إنشاء السند كـ DRAFT بإجماليات صفرية
                $sale = PosSale::create([
                    'branch_id'       => $header->branch_id,
                    'store_id'        => $storeId,
                    'sale_date'       => $header->date, // أو now() حسب منطقك
                    'status'          => PosSale::STATUS_DRAFT,
                    'total_quantity'  => 0,
                    'total_amount'    => 0,
                    'cancelled'       => false,
                    'cancel_reason'   => null,
                    'notes'           => $header->notes,
                    'created_by'      => $header->created_by,
                    'updated_by'      => $header->created_by,
                ]);

                // 2) إنشاء البنود من تفاصيل الاستيراد
                foreach ($header->details as $detail) {
                    $product = $detail->product;

                    if (! $product) {
                        continue;
                    }

                    $unitId = $detail->unit_id;
                    $lineQty = (float) $detail->quantity;

                    if ($lineQty <= 0) {
                        continue;
                    }

                    // جلب سعر الوحدة
                    $unitPrice = $product->unitPrices()
                        ->where('unit_id', $unitId)
                        ->first();

                    $unitPriceValue = (float) ($unitPrice?->price ?? 0);
                    $packageSize    = (float) ($unitPrice?->package_size ?? 1);

                    $lineTotal = $lineQty * $unitPriceValue;

                    Log::info('zzz', [$product]);
                    foreach ($product->productItems as $productItem) {

                        $childProduct = $productItem->product;

                        // إذا كان الـ item غير مربوط بمنتج لأي سبب، نتجاوزه
                        if (! $childProduct) {
                            continue;
                        }

                        $unitId = $productItem->unit_id;

                        // جلب سعر الوحدة من UnitPrice الخاصة بالمنتج الطفل بهذا الـ unit_id
                        $unitPrice = $childProduct->unitPrices()
                            ->where('unit_id', $unitId)
                            ->first();

                        $unitPriceValue = (float) ($unitPrice?->price ?? 0);
                        $packageSize    = (float) ($unitPrice?->package_size ?? $productItem->package_size ?? 1);

                        // الكمية = كمية الـ item * كمية البيع المدخلة من المستخدم
                        $lineQty   = (float) $productItem->quantity * $lineQty;
                        $lineTotal = $lineQty * $unitPriceValue;

                        // إنشاء البند عبر العلاقة items()
                        $sale->items()->create([
                            'product_id'   => $childProduct->id,
                            'unit_id'      => $unitId,
                            'quantity'     => $lineQty,
                            'price'        => $unitPriceValue,
                            'total_price'  => $lineTotal,
                            'package_size' => $packageSize,
                            'notes'        => "Auto from Product {$product->name} (Test POS)",
                        ]);
                    }
                }

                // تحميل البنود وإعادة حساب الإجماليات
                $sale->loadMissing('items');
                $sale->recalculateTotals();

                // تحويل الحالة إلى مكتملة
                $sale->status     = PosSale::STATUS_COMPLETED;
                $sale->updated_by = $header->created_by;
                $sale->save();

                // (اختياري) ربط رأس الاستيراد بالسند
                if ($sale && $header->isFillable('pos_sale_id')) {
                    $header->update([
                        'pos_sale_id' => $sale->id,
                    ]);
                }

                return $sale;
            });

            // 3) بعد نجاح الـ Transaction، ننشئ حركات المخزون من البنود
            if ($sale) {
                $sale->refresh();
                $sale->createInventoryTransactionsFromItems();
            }
        } catch (\Throwable $e) {
            Log::error('Create PosSale from POS Import failed', [
                'header_id' => $this->header?->id,
                'message'   => $e->getMessage(),
            ]);
        }
    }

    // ======== Helpers ========

    protected function firstFilled(array $row, array $keys): ?string
    {
        foreach ($keys as $k) {
            if (array_key_exists($k, $row) && Str::of((string)$row[$k])->trim()->isNotEmpty()) {
                return trim((string) $row[$k]);
            }
        }
        return null;
    }

    protected function resolveUnitId(?string $unitName, Product $product): ?int
    {
        if ($unitName) {
            $unit = Unit::where('name', trim($unitName))->first();
            if ($unit) {
                return $unit->id;
            }
        }

        if ($this->defaultUnitId) {
            return $this->defaultUnitId;
        }

        if (isset($product->unit_id) && $product->unit_id) {
            return (int) $product->unit_id;
        }

        return null;
    }
}
