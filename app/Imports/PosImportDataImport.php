<?php

namespace App\Imports;

use App\Models\PosImportData;
use App\Models\PosImportDataDetail;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class PosImportDataImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use SkipsFailures;

    /** @var PosImportData */
    protected $header;

    /** @var int */
    protected int $successCount = 0;

    /**
     * الثوابت الأساسية تُمرر عبر الـ constructor وتستخدم لإنشاء رأس الاستيراد
     *
     * @param  int         $branchId
     * @param  int         $createdBy
     * @param  string      $date        // 'Y-m-d'
     * @param  string|null $notes
     * @param  int|null    $defaultUnitId  // في حال لم يذكر في الملف
     */
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
            // قراءة مرنة لرؤوس الأعمدة
            $name  = $this->firstFilled($row, ['product', 'item', 'name', 'product_name']);
            $qty   = $this->firstFilled($row, ['qty', 'quantity', 'qty_total', 'grand_total_qty']);
            $unitN = $this->firstFilled($row, ['unit', 'uom']);

            // سطور فارغة / غير مكتملة
            if (!$name || (!is_numeric($qty) && $qty !== 0 && $qty !== '0')) {
                return null;
            }

            $qty = (float) $qty;
            if ($qty == 0.0) {
                // لا فائدة من تخزين كمية صفر
                return null;
            }

            // ابحث عن المنتج بالاسم (تعديلها حسب منطقك: بالاسم/الكود…)
            $product = Product::where('name', trim($name))->first();

            if (!$product) {
                // لو المنتج غير موجود نتجاوز السطر
                Log::warning('POS Import: product not found', ['name' => $name]);
                return null;
            }

            // تحديد الوحدة:
            $unitId = $this->resolveUnitId($unitN, $product);

            if (!$unitId) {
                Log::warning('POS Import: unit not resolved', ['product' => $product->id, 'unit' => $unitN]);
                return null;
            }

            // إنشاء بند التفاصيل
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

        // نعيد null لأننا ندير الإنشاء يدوياً
        return null;
    }

    /**
     * قواعد التحقق على مستوى الأعمدة
     */
    public function rules(): array
    {
        // لأننا نقرأ رؤوس مرنة، نجعلها "أحيانًا" حسب ما وُجد في الملف
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

    // ======== Helpers ========

    /**
     * أرجع أول قيمة غير فارغة لعدة مفاتيح محتمَلة
     */
    protected function firstFilled(array $row, array $keys): ?string
    {
        foreach ($keys as $k) {
            if (array_key_exists($k, $row) && Str::of((string)$row[$k])->trim()->isNotEmpty()) {
                return trim((string) $row[$k]);
            }
        }
        return null;
    }

    /**
     * يحل الوحدة بالترتيب:
     * 1) إن وُجد اسم وحدة في الملف → بالاسم
     * 2) إن وُجد defaultUnitId في المُنشئ
     * 3) إن كان للمنتج عمود unit_id (افتراضي)
     */
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

        // إن كان لديك products.unit_id كوحدة افتراضية
        if (isset($product->unit_id) && $product->unit_id) {
            return (int) $product->unit_id;
        }

        return null;
    }
}
