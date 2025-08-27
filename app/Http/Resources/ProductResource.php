<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'product_id' => $this->id,
            'product_name' => $this->name,
            'product_code' => $this->code,
            // 'is_manufacturing' => $this->is_manufacturing,
            'cat_id' => $this->category->id,
            'cat_name' => $this->category->name,
            'unitPrices' => $this->outUnitPrices,
            'productItems' => $this->productItems,
        ];
    }
}