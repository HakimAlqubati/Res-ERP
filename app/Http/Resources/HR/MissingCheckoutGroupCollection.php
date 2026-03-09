<?php

namespace App\Http\Resources\HR;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class MissingCheckoutGroupCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        $groupedData = [];

        // Group the records by employee
        foreach ($this->collection->groupBy('employee_id') as $employeeId => $items) {
            $firstItem = $items->first();
            $missedCount = $items->count();

            // Strip out redundant fields from each item
            $cleanedItems = $items->map(function ($item) {
                $arr = is_object($item) && method_exists($item, 'toArray') ? $item->toArray() : (array) $item;
                unset($arr['employee_id'], $arr['employee_name'], $arr['branch_id']);
                return $arr;
            })->values();

            // Try to extract branch name if passed, otherwise fallback to fetching it
            $branchName = $firstItem['branch_name'] ?? null;
            if (!$branchName && isset($firstItem['branch_id'])) {
                $branch = \App\Models\Branch::find($firstItem['branch_id']);
                $branchName = $branch ? $branch->name : null;
            }

            $groupedData[] = [
                'employee_id'   => $employeeId,
                'employee_name' => $firstItem['employee_name'] ?? ('Employee ' . $employeeId),
                'branch_id'     => $firstItem['branch_id'] ?? null,
                'branch_name'   => $branchName,
                'message'       => "Employee has {$missedCount} missing checkout" . ($missedCount > 1 ? 's' : '') . ".",
                'missing_count' => $missedCount,
                'items'         => $cleanedItems,
            ];
        }

        return $groupedData;
    }
}
