<?php

namespace App\Filament\Clusters\HRServiceRequestCluster\Resources\EquipmentResource\Services;

use App\Models\Equipment;
use App\Models\EquipmentType;
use Illuminate\Support\Facades\DB;

class EquipmentCodeGenerator
{
    /**
     * توليد كود المعدة بناءً على نوعها
     * Generate equipment code based on type
     *
     * @param int|null $typeId
     * @return string
     */
    public static function generate(?int $typeId): string
    {
        return DB::transaction(function () use ($typeId) {
            // جلب نوع الجهاز مع علاقته بالفئة
            $equipmentType = EquipmentType::with('category')->find($typeId);

            // استخراج البوادئ من الفئة والنوع، أو تعيين قيم افتراضية
            $categoryPrefix = $equipmentType?->category?->equipment_code_start_with ?? 'EQ-';
            $typeCode       = $equipmentType?->code ?? 'GEN';

            // دمج البادئة النهائية: CategoryPrefix + TypeCode
            $prefix = $categoryPrefix . '-' . $typeCode;

            // قفل السجلات المماثلة لمنع التكرار
            $lastAssetTag = Equipment::where('asset_tag', 'like', $prefix . '%')
                ->lockForUpdate()
                ->orderByDesc('asset_tag')
                ->value('asset_tag');

            // استخراج الرقم الأخير إن وُجد
            $lastNumber = 0;
            if ($lastAssetTag && preg_match('/(\d+)$/', $lastAssetTag, $matches)) {
                $lastNumber = (int) $matches[1];
            }

            // توليد الرقم الجديد
            $nextNumber = $lastNumber + 1;

            // إعادة الكود الكامل بالشكل: CATEGORYPREFIX + TYPECODE + 3 أرقام
            return $prefix . '-' . str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT);
        });
    }
}
