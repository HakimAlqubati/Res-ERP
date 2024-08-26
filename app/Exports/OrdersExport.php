<?php

namespace App\Exports;

use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Contracts\View\View;
use stdClass;

class OrdersExport implements FromView
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public $id;
    function __construct($id)
    {
        $this->id = $id;
    }
    public function view(): View
    {
        $order = Order::where('id', $this->id)->get();
        $orderDetails = OrderDetails::where('order_id', $order[0]->id)->get();
        $objOrder = new stdClass();
        $objOrder->orderId = $order[0]->id;
        $objOrder->createdBy = $order[0]->customer_id;
        $objOrder->createdByUserName =  User::where('id', $order[0]->customer_id)->get()[0]->name;
        $objOrder->createdAt = $order[0]->created_at;
        $objOrder->stateId = '';
        $objOrder->state_name =  '';
        $objOrder->restricted_state_name =  '';
        $objOrder->desc = $order[0]->desc;
        $objOrder->branch_id = $order[0]['branch_id'];

        $objOrder->branch_name =  Branch::where('id', $order[0]['branch_id'])->get()[0]?->name;
        $objOrder->manager_name = User::get()[0]->name;
        $objOrder->notes =   $order[0]->notes;



        $finalResult[] = $objOrder;
        foreach ($orderDetails as $key => $value) {
            $obj = new stdClass();
            $obj->product_id = $value->product_id ?? '--';

            $obj->product_name =  Product::find($value->product_id)->name;
            $obj->product_code = Product::find($value->product_id)->code;
            $obj->product_desc =  Product::find($value->product_id)->description;
            $obj->unit_id = $value->unit_id;
            $obj->unit_name = Unit::find($value->unit_id)->name;
            $obj->price =  $value->price;
            $obj->qty = $value->quantity;
            $obj->available_qty = $value->available_quantity;
            array_push($finalResult, $obj);
        }
        return view(
            'export.export_excel',
            compact('finalResult')
        );
    }
}
