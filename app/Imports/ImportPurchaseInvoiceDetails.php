<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\Product;
use App\Models\PurchaseInvoiceDetail;
use App\Models\Unit;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\ToModel;

class ImportPurchaseInvoiceDetails implements ToModel
{
    /**
     * @param Collection $collection
     */
    public function model(array $row)
    {
        $purchase_invoice_detail = new PurchaseInvoiceDetail();
        // $purchase_invoice_detail->purchase_invoice_id = 1;
        // $purchase_invoice_detail->product_id = 1;
        // $purchase_invoice_detail->unit_id = 1;
        // $purchase_invoice_detail->price = 1;
        // $purchase_invoice_detail->quantity = 0;
        // $purchase_invoice_detail->save();
        // return;
        $unit_id = 0;

        if (isset($row[3]) && !is_null($row[3]) && $row[3] != 0) {
            $unit_id  = Unit::where('code', $row[3])->first()?->id; 
        }
        $quantity = 0;
        if ((isset($row[2]) && $row[2] > 0)) {
            $quantity = $row[2];
        }
        $purchase_invoice_detail->product_id = $row[0];
        $purchase_invoice_detail->unit_id = $unit_id;
        $purchase_invoice_detail->quantity = $quantity;
        $purchase_invoice_detail->price = 1;
        $purchase_invoice_detail->purchase_invoice_id = 1;

        // Save the purchase invoice detail
        $purchase_invoice_detail->save();
        if ($unit_id > 0)
            return $purchase_invoice_detail;
        else if ($unit_id == 0)
            return null;
    }
}
