<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        //new code


        // $orderDetails =  OrderDetailsResource::collection($this->orderDetails);
        // if (
        //     isBranchManager() &&
        //     auth()->user()->branch->is_central_kitchen &&
        //     auth()->user()->branch->manager_abel_show_orders
        // ) {

        //     $orderDetails = OrderDetailsResource::collection(
        //         $this->orderDetails()->manufacturingOnlyForStore()->get()
        //     );
        // }    // 👇 تحقق من الفارغ هنا
        // if ($orderDetails->isEmpty()) {
        //     return null;
        // }
        // تحقق من صلاحية المدير لعرض المنتجات التصنيعية فقط
        $isBranchManagerWithPermission = isBranchManager() &&
            optional(auth()->user()->branch)->is_kitchen &&
            optional(auth()->user()->branch)->manager_abel_show_orders;
        // تحديد تفاصيل الطلبات بناءً على الصلاحيات
        $orderDetails = $isBranchManagerWithPermission
            ? OrderDetailsResource::collection(
                $this->orderDetails()->manufacturingOnlyForStore()->get()
            )
            : OrderDetailsResource::collection($this->orderDetails);

        
        // إذا ماكو تفاصيل يرجع null
        if ($orderDetails->isEmpty()) {
            return null;
        }
        // $orderDetails = OrderDetailsResource::collection($this->orderDetails);
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'type' => $this->type,
            'desc' => $this->description,
            'created_by' => $this->customer_id,
            'created_by_user_name' => $this?->customer?->name,
            'request_state_name' => $this->status,
            'branch_id' => $this->branch_id,
            'branch_name' => $this?->branch?->name,
            'notes' => $this->notes,
            'storeuser_id_update' => $this?->storeuser_id_update,
            'storeuser_name' => $this?->storeEmpResponsiple?->name,
            'total_price' => $this->total,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'order_details' => $orderDetails,
        ];
    }
}
