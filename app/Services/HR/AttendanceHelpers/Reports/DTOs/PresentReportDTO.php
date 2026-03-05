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
        $presentCount = $this->presentCount();

        return [
            'status'   => 'success',
            'datetime' => $this->datetime->toDateTimeString(),
            'data'     => [
                'present' => [
                    'label'           => 'Present',
                    'message'         => "{$presentCount} present out of {$this->totalEmployees} total employees.",
                    'count'           => $presentCount,
                    'total_employees' => $this->totalEmployees,
                    'items'           => $this->present,
                ],
                'absent'  => [
                    'label'   => 'Absent',
                    'message' => 'Employees assigned to an active shift but have not checked in yet.',
                    'count'   => $this->absentCount(),
                    'items'   => $this->expectedAbsent,
                ],
            ],
        ];
    }

    public function toResponse(): \Illuminate\Http\JsonResponse
    {
        return response()->json($this);
    }
}
