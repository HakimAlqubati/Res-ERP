<?php

namespace App\Http\Resources\HR;

use Illuminate\Http\Resources\Json\JsonResource;

class DeductionResource extends JsonResource
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
            'id'                     => $this->id,
            'name'                   => $this->name,
            'description'            => $this->description,
            'is_monthly'             => (bool) $this->is_monthly,
            'active'                 => (bool) $this->active,
            'is_penalty'             => (bool) $this->is_penalty,
            'is_specific'            => (bool) $this->is_specific,
            'amount'                 => (float) $this->amount,
            'percentage'             => (float) $this->percentage,
            'is_percentage'          => (bool) $this->is_percentage,
            'is_mtd_deduction'       => (bool) $this->is_mtd_deduction,
            'applied_by'             => $this->applied_by,
            'has_brackets'           => (bool) $this->has_brackets,
            'created_at'             => $this->created_at?->toDateTimeString(),
            'updated_at'             => $this->updated_at?->toDateTimeString(),
        ];
    }
}
