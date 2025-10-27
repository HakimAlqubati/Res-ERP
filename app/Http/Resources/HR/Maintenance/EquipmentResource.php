<?php 
namespace App\Http\Resources\HR\Maintenance;

use App\Http\Resources\BranchResource;
use App\Http\Resources\HR\Maintenance\EquipmentCategoryResource;
use App\Http\Resources\HR\Maintenance\EquipmentTypeResource;
use App\Http\Resources\MediaResource;
use Illuminate\Http\Resources\Json\JsonResource;

class EquipmentResource extends JsonResource {
    public function toArray($request){
        return [
            'id' => $this->id,
            'qr_code' => $this->qr_code,
            'asset_tag' => $this->asset_tag,
            'name' => $this->name,
            'make' => $this->make,
            'model' => $this->model,
            'serial_number' => $this->serial_number,
            'status' => $this->status,
            'type' => new EquipmentTypeResource($this->whenLoaded('type')),
            'category' => new EquipmentCategoryResource($this->whenLoaded('category')),
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'branch_area_id' => $this->branch_area_id,
            'purchase_price' => $this->purchase_price,
            'purchase_date' => $this->purchase_date,
            'warranty_years' => $this->warranty_years,
            'warranty_months' => $this->warranty_months,
            'warranty_end_date' => $this->warranty_end_date,
            'periodic_service' => (bool)$this->periodic_service,
            'service_interval_days' => $this->service_interval_days,
            'last_serviced' => $this->last_serviced,
            'next_service_date' => $this->next_service_date,
            'media' => MediaResource::collection($this->getMedia()),
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
