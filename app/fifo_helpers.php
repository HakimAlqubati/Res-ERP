<?php

use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceDetail;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\DB;

/**
 * get sum qty of specific product with (product_id,unit_id) from purchase invoices
 */
function getSumQtyOfProductFromPurchases($product_id, $unit_id, $latest = false)
{

    $query = DB::table('purchase_invoice_details')
        ->select(
            DB::raw('SUM(quantity) as total_quantity'),
            'price',
            'purchase_invoice_id'
        )
        ->join(
            'purchase_invoices',
            'purchase_invoice_details.purchase_invoice_id',
            '=',
            'purchase_invoices.id'
        )
        ->where('product_id', $product_id)
        // ->where('purchase_invoices.active', 1)
        ->where('unit_id', $unit_id)
        ->groupBy('price', 'purchase_invoice_id')
        ->orderBy('purchase_invoice_id', 'asc');
        $query->whereNull('purchase_invoices.deleted_at');
    if (!$latest) {
        $result = $query->get();
    } else {
        $result = $query->get()->last();
    }

    return $result;
}

/**
 * get sum qty of specific product with (product_id,unit_id) from orders
 */
function getSumQtyOfProductFromOrders($product_id, $unit_id, $purchase_invoic_id)
{

    $orderdQty = DB::table('orders_details')
        ->join('orders', 'orders_details.order_id', '=', 'orders.id')
        // ->whereIn('orders.status', [Order::DELEVIRED, Order::READY_FOR_DELEVIRY])
        ->where('orders_details.product_id', $product_id)
        ->where('orders_details.unit_id', $unit_id)
        // ->where('orders.active', 1)
        // ->where('orders.active', 1)
        ->whereNull('orders.deleted_at')
        ->where('orders_details.purchase_invoice_id', $purchase_invoic_id)
        ->select(DB::raw('SUM(available_quantity) as total_available_quantity'))
        ->first()->total_available_quantity;
    if ($orderdQty == null) {
        $orderdQty = 0;
    }
    return $orderdQty;
}



/**
 * to compare purchased quantities with orderd quantities
 */
function comparePurchasedWithOrderdQties($product_id, $unit_id)
{
    $fdata = [];
    $data = getSumQtyOfProductFromPurchases($product_id, $unit_id, false);


    foreach ($data as $i => $value) {
        $purchased_qty = $value->total_quantity;
        $price = $value->price;
        $purchase_invoice_id = $value->purchase_invoice_id;
        $ordersQty = getSumQtyOfProductFromOrders($product_id, $unit_id, $purchase_invoice_id);
        if ($purchased_qty > $ordersQty) {
            $fdata[] = [
                'purchased_qty' => $purchased_qty,
                'price' => $price,
                'purchase_invoice_id' => $purchase_invoice_id,
                'orderd_qty' => $ordersQty,
                'remaning_qty' => $purchased_qty - $ordersQty,
                'product_id' => $product_id,
                'unit_id' => $unit_id,
            ];
        }
    }

    if (isOrderCompletedIfQtyLessThanZero() && count($fdata) == 0) {

        $latestProductPurchased = getSumQtyOfProductFromPurchases($product_id, $unit_id, true);
        if (!is_null($latestProductPurchased)) {
            $fdata[] = [
                'purchased_qty' => 0,
                'price' => $latestProductPurchased->price,
                'purchase_invoice_id' => $latestProductPurchased->purchase_invoice_id,
                'orderd_qty' => 0,
                'remaning_qty' => 0,
                'product_id' => $product_id,
                'unit_id' => $unit_id,
            ];
        } else {
            if (is_numeric(getUnitPrice($product_id, $unit_id)) && getUnitPrice($product_id, $unit_id) > 0) {
                $fdata[] = [
                    'purchased_qty' => 0,
                    'price' => getUnitPrice($product_id, $unit_id),
                    'purchase_invoice_id' => null,
                    'orderd_qty' => 0,
                    'remaning_qty' => 0,
                    'product_id' => $product_id,
                    'unit_id' => $unit_id,
                ];
            }
        }
    }

    return $fdata;
}

/**
 * calculate using fifo
 */
function calculateFifoMethod($req_array, $orderId)
{
    $orderDetailsData = [];
    $finalOrderDetailData = [];
    foreach ($req_array as  $req_val) {
        $comparedData =  comparePurchasedWithOrderdQties($req_val['product_id'], $req_val['unit_id']);

        if (count($comparedData) == 1 && $comparedData[0]['purchased_qty'] == 0 && isOrderCompletedIfQtyLessThanZero()) {

            $orderDetailsData  = [
                'order_id' => $orderId,
                'product_id' => $comparedData[0]['product_id'],
                'unit_id' => $comparedData[0]['unit_id'],
                'quantity' => $req_val['quantity'],
                'available_quantity' => $req_val['quantity'],
                'created_by' => auth()?->user()?->id,
                'purchase_invoice_id' => $comparedData[0]['purchase_invoice_id'],
                'price' =>  $comparedData[0]['price'],
                'negative_inventory_quantity' => true,
            ];
            $finalOrderDetailData[] = $orderDetailsData;
            continue;
        }

        foreach ($comparedData as $key => $value) {
            $price = $value['price'];
            $purchase_invoice_id = $value['purchase_invoice_id'];
            $remaning_qty = $value['remaning_qty'];
            $already_ordered_qty = $req_val['quantity'];
            if ($already_ordered_qty <= $remaning_qty) {
                $orderDetailsData  = [
                    'order_id' => $orderId,
                    'product_id' => $value['product_id'],
                    'unit_id' => $value['unit_id'],
                    'quantity' => $already_ordered_qty,
                    'available_quantity' => $already_ordered_qty,
                    'created_by' => auth()?->user()?->id,
                    'purchase_invoice_id' => $purchase_invoice_id,
                    'price' =>  $price,
                    'negative_inventory_quantity' => false,
                ];
                break;
            } else if ($already_ordered_qty > $remaning_qty) {
                $orderDetailsData = calculateIfAlreadyQtyBiggerThanRemaning($comparedData, $already_ordered_qty, $value['product_id'], $value['unit_id'], $orderId);
                break;
            }
        }
        $finalOrderDetailData[] = $orderDetailsData;
    }


    return fixResultOfCalculating($finalOrderDetailData);
}

/**
 * function does that 
 * calculate the price if already ordered quantity is bigger than remaning quantity
 */
function calculateIfAlreadyQtyBiggerThanRemaning($comparedData, $already_ordered_qty, $product_id, $unit_id, $orderId)
{
    $orderDetailsData = [];
    for ($i = 0; $i < count($comparedData); $i++) {


        if ($already_ordered_qty > $comparedData[$i]['remaning_qty']) {
            $qty = ($comparedData[$i]['purchased_qty'] - $comparedData[$i]['orderd_qty']);
        } else if ($already_ordered_qty <= $comparedData[$i]['remaning_qty']) {
            $qty = $already_ordered_qty;
        }



        $orderDetailsData[] = [
            'order_id' => $orderId,
            'product_id' => $product_id,
            'unit_id' => $unit_id,
            'quantity' => $qty,
            'available_quantity' => $qty,
            'created_by' => auth()?->user()?->id,
            'purchase_invoice_id' => $comparedData[$i]['purchase_invoice_id'],
            'price' => $comparedData[$i]['price'],
            'negative_inventory_quantity' => false,
        ];
        $already_ordered_qty = ($already_ordered_qty - $qty);

        if ($already_ordered_qty > 0  && $i == count($comparedData) - 1 && isOrderCompletedIfQtyLessThanZero()) {
            $orderDetailsData[] = [
                'order_id' => $orderId,
                'product_id' => $product_id,
                'unit_id' => $unit_id,
                'quantity' => $already_ordered_qty,
                'available_quantity' => $already_ordered_qty,
                'created_by' => auth()?->user()?->id,
                'purchase_invoice_id' => $comparedData[$i]['purchase_invoice_id'],
                'price' => $comparedData[$i]['price'],
                'negative_inventory_quantity' => true,
            ];
        }

        if ($already_ordered_qty > 0) {
            continue;
        } else {
            break;
        }
    }

    return $orderDetailsData;
}


/**
 * fixing the result of calculating function
 */
function fixResultOfCalculating($finalOrderDetailData)
{
    $mergedArray = [];
    foreach ($finalOrderDetailData as $i => $item) {

        if (!isset($item['order_id'])) {
            foreach ($item as $subItem) {
                $mergedArray[] = (array) $subItem;
            }
        } else {
            $mergedArray[] = (array) $item;
        }
    }
    return $mergedArray;
}


/**
 * complete order if the quantities less than zero
 * that means the prices will be equal the last products
 */
function isOrderCompletedIfQtyLessThanZero()
{

    return   SystemSetting::select('completed_order_if_not_qty')->first()->completed_order_if_not_qty;
}


/**
 * handle pending order details
 */
function handlePendingOrderDetails($pendingOrderDetails)
{
    $newOrderDetailsInPendingOrder = [];
    foreach ($pendingOrderDetails as $key => $value) {
        $existOrderDetail = OrderDetails::where(
            'order_id',
            $value['order_id']
        )->where(
            'product_id',
            $value['product_id']
        )->where(
            'unit_id',
            $value['unit_id']
        )
            ->where(
                'purchase_invoice_id',
                $value['purchase_invoice_id']
            )
            ->where(
                'negative_inventory_quantity',
                $value['negative_inventory_quantity']
            )
            ->first();
        if ($existOrderDetail) {
            $newQuantity = $existOrderDetail->quantity + $value['quantity'];
            $existOrderDetail->update([
                'updated_by' => auth()->user()->id,
                'quantity' => $newQuantity,
                'available_quantity' => $newQuantity,
                'price' => $value['price'],
            ]);
        } else {
            $newOrderDetailsInPendingOrder[] = $value;
        }
    }

    if (count($newOrderDetailsInPendingOrder) > 0) {
        OrderDetails::insert($newOrderDetailsInPendingOrder);
    }
}

/**
 * get price of product from purchase invoice using (product_id, unit_id, purchase_invoice_id)
 */
function getProductPriceByProductUnitPurchaseInvoiceId($productId, $unitId, $purchaseInvoiceId)
{
    $price = 0;
    $purchaseInvoiceDetail = PurchaseInvoiceDetail::where([
        'purchase_invoice_id' => $purchaseInvoiceId,
        'product_id' => $productId,
        'unit_id' => $unitId,

    ])->first();
    if ($purchaseInvoiceDetail) {
        $price = $purchaseInvoiceDetail->price;
    }
    return $price;
}


function getProductQuantities($productId, $unitId, $orderDetailId, $purchaseInvoiceId)
{
    $result = [];
    $checkIfPurchaseInvoiceId = PurchaseInvoice::find($purchaseInvoiceId);
    if ($checkIfPurchaseInvoiceId) {
        $data = DB::select("
        SELECT 
        (
            SELECT SUM(od.available_quantity) 
            FROM orders_details AS od
            JOIN orders AS o ON od.order_id = o.id 
            WHERE od.product_id = $productId
            AND o.deleted_at IS NULL 
            AND od.unit_id = $unitId
            and od.id <= $orderDetailId
            and od.purchase_invoice_id = $purchaseInvoiceId
        ) AS total_ordered_qty,
        
        (
            SELECT SUM(pd.quantity) 
            FROM purchase_invoice_details AS pd
            JOIN purchase_invoices AS p ON pd.purchase_invoice_id = p.id 
            WHERE pd.product_id = $productId
            AND p.deleted_at IS NULL 
            AND pd.unit_id = $unitId
            and pd.purchase_invoice_id = $purchaseInvoiceId
        ) AS total_purchased_qty
        
        
    FROM 
        orders_details AS ord_dts
    WHERE 
        ord_dts.product_id = $productId
      and   ord_dts.unit_id  = $unitId
      and purchase_invoices.deleted_at is null
    GROUP BY 
        ord_dts.product_id
        ;
        ");
        if (count($data) > 0) {
            $result = [
                'total_purchased_qty' => $data[0]->total_purchased_qty,
                'total_ordered_qty' => $data[0]->total_ordered_qty,
                'remaning_qty' => $data[0]->total_purchased_qty - $data[0]->total_ordered_qty,
            ];
        }
    }
    return $result;
}
