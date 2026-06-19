<?php

declare(strict_types=1);

namespace App\Modules\Stock\Reports\StockBalanceReport\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockBalanceResource extends JsonResource
{
    /**
     * تحويل الكائن إلى مصفوفة نظيفة تحتوي على الحقول الديناميكية.
     */
    public function toArray(Request $request): array
    {
        return [
            'product_id'   => $this->id,
            'product_code' => $this->code,
            'product_name' => $this->name,
            'category_id'  => $this->category_id,
            // 'category_name'=> $this->category->name,
            
            // 🔥 إضافة بيانات الوحدة الأساسية التي جلبناها من الـ Subquery
            'base_unit'         => $this->base_unit_name ?? 'N/A',
            'base_package_size' => (float) ($this->base_package_size ?? 1),

            // 🔥 هنا نظهر الحقول الديناميكية التي حسبناها في قاعدة البيانات
            'total_in'           => (float) $this->total_in,
            'total_out'          => (float) $this->total_out,
            'remaining_base_qty' => (float) $this->remaining_base_qty,

            // إذا كان لديك وحدات مسحوبة مسبقاً يمكنك إضافتها هنا لاحقاً
            // 'unit_prices' => $this->whenLoaded('reportUnitPrices'),
        ];
    }
}