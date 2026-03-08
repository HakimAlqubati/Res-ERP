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

        foreach ($this->collection as $date => $records) {
            $groupedData[] = [
                'date' => $date,
                'records' => OvertimeResource::collection($records),
            ];
        }

        return $groupedData;
    }
}
