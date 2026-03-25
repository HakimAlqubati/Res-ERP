<?php

namespace App\Modules\HR\Overtime\V2\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class OvertimeGroupCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     * 
     * The input collection is expected to be grouped by date:
     * [
     *    '2023-10-01' => Collection of EmployeeOvertime,
     *    '2023-10-02' => Collection of EmployeeOvertime,
     * ]
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        $groupedData = [];
        $includeEmployees = $request->boolean('include_employees');

        foreach ($this->collection as $date => $records) {
            $totalEmployees = $records->pluck('employee_id')->unique()->count();
            $totalHours = $records->sum('hours');

            $group = [
                'date'            => $date,
                'total_employees' => $totalEmployees,
                'total_hours'     => round((float) $totalHours, 2),
            ];

            if ($includeEmployees) {
                $group['records'] = OvertimeResource::collection($records);
            }

            $groupedData[] = $group;
        }

        return $groupedData;
    }
}
