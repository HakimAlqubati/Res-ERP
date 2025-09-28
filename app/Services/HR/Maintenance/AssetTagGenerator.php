<?php

namespace App\Services\HR\Maintenance;

use App\Models\Equipment;
use App\Models\EquipmentType;
use Illuminate\Support\Str;

class AssetTagGenerator
{
    /**
     * توليد كود Asset Tag لمعدة جديدة
     *
     * الصيغة: {CategoryPrefix}-{TypeCode}-{Increment}
     * مثال: CMP-HP-0001
     */
    public function generate(int $typeId): string
    {
        /** @var EquipmentType $type */
        $type = EquipmentType::with('category')->findOrFail($typeId);

        $categoryPrefix = strtoupper($type->category->equipment_code_start_with ?? 'GEN');
        $typeCode       = strtoupper($type->code ?? Str::slug($type->name));

        // اجلب آخر asset_tag بنفس prefix
        $lastEquipment = Equipment::whereHas('type', function ($q) use ($type) {
                $q->where('category_id', $type->category_id);
            })
            ->where('asset_tag', 'like', $categoryPrefix . '-' . $typeCode . '-%')
            ->orderByDesc('id')
            ->first();

        // استخرج الرقم التسلسلي
        $nextNumber = 1;
        if ($lastEquipment) {
            $parts = explode('-', $lastEquipment->asset_tag);
            $lastNum = intval(end($parts));
            $nextNumber = $lastNum + 1;
        }

        return sprintf("%s-%s-%04d", $categoryPrefix, $typeCode, $nextNumber);
    }
}
