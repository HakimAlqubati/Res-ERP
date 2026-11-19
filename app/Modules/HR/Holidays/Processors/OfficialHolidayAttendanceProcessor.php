<?php

declare(strict_types=1);

namespace App\Modules\HR\Holidays\Processors;

use App\Enums\HR\Attendance\AttendanceReportStatus;
use App\Models\Employee;
use App\Models\HolidayOccurrence;
use App\Modules\HR\AttendanceReports\Processors\AttendanceDayProcessor;
use App\Modules\HR\Holidays\Builders\HolidayOccurrenceQueryBuilder;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Isolated processor responsible for resolving confirmed official holidays
 * and integrating them into attendance reports without altering existing day processor logic.
 */
final class OfficialHolidayAttendanceProcessor
{
    /**
     * Pre-fetch all active, confirmed holiday occurrences overlapping the given date range in O(1) query.
     *
     * @return Collection<int, HolidayOccurrence>
     */
    public static function loadConfirmedForRange(string $startDateStr, string $endDateStr): Collection
    {
        return HolidayOccurrenceQueryBuilder::make()
            ->onlyActive()
            ->forStatus(HolidayOccurrence::STATUS_CONFIRMED)
            ->build()
            ->overlappingRange($startDateStr, $endDateStr)
            ->orderBy('from_date')
            ->get();
    }

    /**
     * Find the applicable confirmed holiday occurrence for a given date and branch.
     *
     * @param  Collection<int, HolidayOccurrence>|null  $holidays
     */
    public static function findApplicableHoliday(
        ?Collection $holidays,
        string $dateStr,
        ?int $branchId
    ): ?HolidayOccurrence {
        if (! $holidays || $holidays->isEmpty()) {
            return null;
        }

        $targetDate = Carbon::parse($dateStr)->startOfDay();

        return $holidays->first(function (HolidayOccurrence $holiday) use ($targetDate, $branchId): bool {
            if (! $holiday->from_date || ! $holiday->to_date) {
                return false;
            }

            $inRange = $targetDate->between(
                $holiday->from_date->copy()->startOfDay(),
                $holiday->to_date->copy()->startOfDay()
            );

            if (! $inRange) {
                return false;
            }

            if ($branchId === null) {
                return (bool) $holiday->all_branches;
            }

            return $holiday->isApplicableToBranch($branchId);
        });
    }

    /**
     * Resolve the effective branch ID and branch name of the employee on a given day.
     *
     * @return array{branch_id: int|null, branch_name: string|null}
     */
    public static function resolveEffectiveBranch(Collection $dayHistories, Employee $employee): array
    {
        $firstHistory = $dayHistories->first();

        return [
            'branch_id'   => $firstHistory?->branch_id ?? $employee->branch_id,
            'branch_name' => $firstHistory?->branch?->name ?? $employee->branch?->name,
        ];
    }

    /**
     * Build the report payload for an official holiday when the employee did NOT attend.
     * Reuses AttendanceDayProcessor::buildLeaveDay() for 100% compatibility with existing
     * statistics, weekly leave calculations, payroll deductions, and Blade templates.
     *
     * @return array<string, mixed>
     */
    public static function buildUnattendedHolidayDay(
        AttendanceDayProcessor $processor,
        string $dateStr,
        string $dayName,
        HolidayOccurrence $holiday,
        ?int $branchId = null,
        ?string $branchName = null
    ): array {
        $syntheticLeave = (object) [
            'transaction_description' => $holiday->name,
            'leave_type'              => null,
        ];

        $dayReport = $processor->buildLeaveDay($dateStr, $dayName, $syntheticLeave, $branchId);
        $dayReport['branch_name'] = $branchName;
        $dayReport['is_official_holiday'] = true;
        $dayReport['holiday_occurrence_id'] = $holiday->id;
        $dayReport['holiday_name'] = $holiday->name;
        $dayReport['worked_on_holiday'] = false;

        return $dayReport;
    }

    /**
     * Decorate the report payload for an official holiday when the employee DID attend.
     * Preserves all check-in/out logs and actual worked hours while ensuring the employee
     * is never penalized as absent on an official holiday.
     *
     * @param  array<string, mixed>  $dayReport
     * @return array<string, mixed>
     */
    public static function decorateWorkedHolidayDay(array $dayReport, HolidayOccurrence $holiday): array
    {
        $dayReport['is_official_holiday'] = true;
        $dayReport['holiday_occurrence_id'] = $holiday->id;
        $dayReport['holiday_name'] = $holiday->name;
        $dayReport['worked_on_holiday'] = true;

        if (in_array($dayReport['day_status'] ?? null, [
            AttendanceReportStatus::Absent->value,
            AttendanceReportStatus::Partial->value,
            AttendanceReportStatus::IncompleteCheckinOnly->value,
            AttendanceReportStatus::IncompleteCheckoutOnly->value,
        ], true)) {
            $dayReport['day_status'] = AttendanceReportStatus::Present->value;
        }

        return $dayReport;
    }
}
