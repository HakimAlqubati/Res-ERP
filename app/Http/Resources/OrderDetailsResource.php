<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderDetailsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id'=>$this->id,
            'order_id'=>$this->order_id,
            'product_id'=>$this->product_id,
            'product_name'=>$this?->product?->name,
            'product_category'=>$this?->product?->category?->id,
            'unit_id'=>$this->unit_id,
            'unit_name'=>$this->unit->name,
            'quantity'=>$this->available_quantity,
            'available_quantity'=>$this->available_quantity,
            'price'=>$this->price,
            'available_in_store'=>$this->available_in_store,
            'created_by'=> $this->createdBy?->id,
            'created_by_user_name'=> $this->createdBy?->name,
        ];
    }
}
