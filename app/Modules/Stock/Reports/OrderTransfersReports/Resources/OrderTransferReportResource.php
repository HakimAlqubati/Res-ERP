<?php

namespace App\Modules\Stock\Reports\OrderTransfersReports\Resources;

use stdClass;

class OrderTransferReportResource
{
    public static function transform(array $rawRows): array
    {
        $formattedData = [];

        foreach ($rawRows as $row) {
            // جلب القيم الجاهزة من الـ SQL مباشرة دون عمليات حسابية معقدة
            $qty = (float) ($row->remaining_qty_unit ?? 0);
            $unitPrice = (float) ($row->unit_price ?? 0);
            $subtotal = (float) ($row->remaining_value ?? 0); // ← تم جلبها جاهزة من الداتابيز

            $obj = new stdClass;
            $obj->code = $row->product_code ?? '';
            $obj->product = $row->product_name ?? '';
            $obj->branch = $row->branch_name ?? '';
            $obj->unit = $row->unit_name ?? '';
            $obj->package_size = (float) ($row->package_size ?? 1);

            $obj->quantity = formatQunantity($qty);
            $obj->in_quantity = formatQunantity((float) ($row->in_qty_base ?? 0));
            $obj->out_quantity = formatQunantity((float) ($row->out_qty_base ?? 0));
            $obj->price = formatMoneyWithCurrency($unitPrice);
            $obj->subtotal = formatMoneyWithCurrency($subtotal); // التنسيق للعرض فقط

            $formattedData[] = $obj;
        }

        return $formattedData;
    }
}
