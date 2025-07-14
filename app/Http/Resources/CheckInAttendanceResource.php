<?php 
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CheckInAttendanceResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'                    => $this->id,
            'check_time'            => $this->check_time,
            'delay_minutes'         => $this->delay_minutes,
            'early_arrival_minutes' => $this->early_arrival_minutes,
            'status'                => $this->status,
            // حقول أخرى تخص الدخول فقط
        ];
    }
}