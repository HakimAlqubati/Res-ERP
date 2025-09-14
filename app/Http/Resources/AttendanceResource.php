<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    /**
     * تحويل الموديل إلى مصفوفة لواجهة الـ API.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id'              => $this->id,
            'check_type'      => $this->check_type,
            'check_time'      => $this->check_time,
            'check_date'      => $this->check_date,
            'period_id'       => $this->period_id,
            'status'          => $this->status,
            'delay_minutes'   => $this->delay_minutes,
            'early_arrival_minutes' => $this->early_arrival_minutes,
            'late_departure_minutes' => $this->late_departure_minutes,
            'early_departure_minutes' => $this->early_departure_minutes,
            'actual_duration_hourly' => $this->actual_duration_hourly,
            'supposed_duration_hourly' => $this->supposed_duration_hourly,
            'total_actual_duration_hourly' => $this->total_actual_duration_hourly,
            // أضف أو احذف الحقول كما تحتاج
            // 'employee'      => new EmployeeResource($this->whenLoaded('employee')),
        ];
    }
}