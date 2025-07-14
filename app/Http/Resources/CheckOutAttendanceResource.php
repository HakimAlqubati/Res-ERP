<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CheckOutAttendanceResource extends JsonResource
{
    protected $approvedOvertime;
    protected $date;

    public function __construct($resource, $approvedOvertime = null, $date = null)
    {
        // أولاً مرر الموديل للـ parent
        parent::__construct($resource);
        $this->approvedOvertime = $approvedOvertime;
        $this->date             = $date;
    }

    public function toArray($request)
    {
        return [
            'id'                       => $this->id,
            'check_time'               => $this->check_time,
            'late_departure_minutes'   => $this->late_departure_minutes,
            'early_departure_minutes'  => $this->early_departure_minutes,
            'actual_duration_hourly'   => $this->actual_duration_hourly,
            'supposed_duration_hourly' => $this->supposed_duration_hourly,
            'status'                   => $this->status,
            'missing_hours'            => calculate_missing_hours(
                $this->status,
                $this->supposed_duration_hourly ?? $this->period . ':00',
                $this->approvedOvertime,
                $this->date,
                $this->employee_id,

            ),
            // حقول أخرى تخص الانصراف فقط
        ];
    }
}