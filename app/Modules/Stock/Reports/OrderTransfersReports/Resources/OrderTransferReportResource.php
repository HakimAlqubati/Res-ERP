<?php

namespace App\Modules\Stock\Reports\OrderTransfersReports\Resources;

use stdClass;

class OrderTransferReportResource
{
    /**
     * تحويل مصفوفة البيانات الخام إلى مخرجات جاهزة للعرض في Blade
     * * @param array $rawRows البيانات القادمة من قاعدة البيانات
     * @return array مصفوفة الكائنات المنسقة
     */
    public static function transform(array $rawRows): array
    {
        $formattedData = [];

        foreach ($rawRows as $row) {
            // حساب الإجمالي كقيمة رقمية بحتة أولاً
            $qty       = (float) ($row->remaining_qty_unit ?? 0);
            $unitPrice = (float) ($row->unit_price ?? 0);
            $subtotal  = $qty * $unitPrice;

            // إنشاء كائن قياسي لتخزين السطر الواحد
            $obj = new stdClass();
            
            // البيانات النصية
            $obj->code         = $row->product_code ?? '';
            $obj->product      = $row->product_name ?? '';
            $obj->branch       = $row->branch_name ?? '';
            $obj->unit         = $row->unit_name ?? '';
            $obj->package_size = (float) ($row->package_size ?? 1);

            // البيانات المنسقة (للعرض المباشر في الـ Blade)
            $obj->quantity     = formatQunantity($qty);
            $obj->in_quantity  = formatQunantity((float) ($row->in_qty_base ?? 0));
            $obj->out_quantity = formatQunantity((float) ($row->out_qty_base ?? 0));
            $obj->price        = formatMoneyWithCurrency($unitPrice);
            $obj->subtotal     = formatMoneyWithCurrency($subtotal);

            // البيانات الخام (لحساب إجمالي التقرير في أسفل الجدول)
            $obj->quantity_raw = $qty;
            $obj->price_raw    = $unitPrice;
            $obj->subtotal_raw = $subtotal;

            $formattedData[] = $obj;
        }

        return $formattedData;
    }
}