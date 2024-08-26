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
        return [
            'id'=>$this->id,
            'desc'=>$this->description,
            'created_by'=>$this->customer_id,
            'created_by_user_name'=>$this?->customer?->name,
            'request_state_name'=>$this->status,
            'branch_id'=>$this->branch_id,
            'branch_name'=>$this?->branch?->name,
            'notes'=>$this->notes,
            'storeuser_id_update' => $this?->storeuser_id_update,
            'storeuser_name' =>$this?->storeEmpResponsiple?->name,
            'total_price'=>$this->total,
            'created_at'=>$this->created_at,
            'updated_at'=>$this->updated_at,
            'order_details'=>OrderDetailsResource::collection($this->orderDetails)
        ];
    }
}
