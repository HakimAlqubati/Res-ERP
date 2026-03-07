<?php

namespace App\Services\HR\AttendanceHelpers\Reports\DTOs;

use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Full attendance snapshot DTO returned by PresentEmployeesService::getReport().
 *
 * Encapsulates both groups (present / expected-absent) and exposes
 * toArray() / toResponse() for clean controller usage.
 */
final readonly class PresentReportDTO implements \JsonSerializable
{
    /**
     * @param  Collection<PresentEmployeeDTO>         $present
     * @param  Collection<ExpectedAbsentEmployeeDTO>  $expectedAbsent
     * @param  int                                    $totalEmployees
     * @param  Carbon                                 $datetime
     */
    public function __construct(
        public Collection $present,
        public Collection $expectedAbsent,
        public int        $totalEmployees,
        public Carbon     $datetime,
        public bool       $hasBranchFilter = true,
    ) {}

    public function presentCount(): int
    {
        return $this->present->count();
    }

    public function absentCount(): int
    {
        return $this->expectedAbsent->count();
    }

    public function jsonSerialize(): array
    {
        // Always group by branch logic, even if there's only one branch from the filter
        $structuredData = [];

        // Group present employees by branchId
        $groupedPresent = $this->present->groupBy(fn($item) => $item->branchId);

        // Group absent employees by branchId
        $groupedAbsent = $this->expectedAbsent->groupBy(fn($item) => $item->branchId);

        // Get all unique branch IDs across both collections
        $allBranchIds = $groupedPresent->keys()->merge($groupedAbsent->keys())->unique();

        foreach ($allBranchIds as $branchId) {
            $branchPresent = $groupedPresent->get($branchId, collect());
            $branchAbsent = $groupedAbsent->get($branchId, collect());

            $bPresentCount = $branchPresent->count();
            $bAbsentCount = $branchAbsent->count();

            // Try to extract branch name from the first available record
            $branchName = 'Unknown Branch';
            $firstRecord = $branchPresent->first() ?? $branchAbsent->first();

            if ($firstRecord) {
                $branch = \App\Models\Branch::find($branchId);
                $branchName = $branch ? $branch->name : 'Unknown Branch';
            }

            // Build the base branch structure
            $branchStructure = [
                'branch_id'   => $branchId,
                'branch_name' => $branchName,
                'attendance_data' => [
                    'present' => [
                        'label'           => 'Present',
                        'message'         => "{$bPresentCount} present" . ($this->hasBranchFilter ? " out of {$this->totalEmployees} total employees." : "."),
                        'count'           => $bPresentCount,
                    ],
                    'absent' => [
                        'label'   => 'Absent',
                        'message' => $this->hasBranchFilter ? 'Employees assigned to an active shift but have not checked in yet.' : "{$bAbsentCount} absent.",
                        'count'   => $bAbsentCount,
                    ]
                ]
            ];

            // If a specific branch filter was requested, include the exact employee items arrays and context.
            if ($this->hasBranchFilter) {
                $branchStructure['attendance_data']['present']['total_employees'] = $this->totalEmployees;
                $branchStructure['attendance_data']['present']['items'] = $branchPresent;
                $branchStructure['attendance_data']['absent']['items'] = $branchAbsent;
            }

            $structuredData[] = $branchStructure;
        }

        return [
            'status'   => 'success',
            'datetime' => $this->datetime->toDateTimeString(),
            'data'     => $structuredData,
        ];
    }

    public function toResponse(): \Illuminate\Http\JsonResponse
    {
        return response()->json($this);
    }
}
