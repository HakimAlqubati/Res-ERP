<?php

declare(strict_types=1);

namespace App\Modules\HR\SalaryIncrease\Services;

use App\Models\Employee;
use App\Models\Payroll;
use App\Models\SalaryIncreaseRequest;
use App\Modules\HR\SalaryIncrease\Builders\EmployeeSalaryTimelineBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Focused service responsible for idempotently backfilling `hr_employee_salary_logs`
 * for the active database connection using `EmployeeSalaryTimelineBuilder`.
 */
final class EmployeeSalaryBackfillService
{
    /**
     * @return array{
     *     skipped_reason: ?string,
     *     scanned: int,
     *     backfilled: int,
     *     skipped_existing: int,
     *     skipped_zero: int,
     *     with_multi_history: int,
     *     logs_to_insert: int
     * }
     */
    public function run(bool $dryRun = false, int $chunkSize = 500): array
    {
        $stats = [
            'skipped_reason' => null,
            'scanned' => 0,
            'backfilled' => 0,
            'skipped_existing' => 0,
            'skipped_zero' => 0,
            'with_multi_history' => 0,
            'logs_to_insert' => 0,
        ];

        if (! Schema::hasTable('hr_employees')) {
            $stats['skipped_reason'] = 'hr_employees table not found.';

            return $stats;
        }

        $hasLogsTable = Schema::hasTable('hr_employee_salary_logs');
        if (! $hasLogsTable && ! $dryRun) {
            $stats['skipped_reason'] = 'hr_employee_salary_logs table not migrated yet.';

            return $stats;
        }

        $hasPayrolls = Schema::hasTable('hr_payrolls') && Schema::hasTable('hr_salary_transactions');
        $hasIncreaseRequests = Schema::hasTable('hr_salary_increase_requests');
        $now = now();
        $currentMonth = $now->copy()->startOfMonth();

        Employee::withTrashed()
            ->withoutGlobalScopes()
            ->select(['id', 'salary', 'join_date', 'created_at'])
            ->orderBy('id')
            ->chunkById(max(50, $chunkSize), function ($employees) use (
                &$stats,
                $hasLogsTable,
                $hasPayrolls,
                $hasIncreaseRequests,
                $dryRun,
                $now,
                $currentMonth
            ): void {
                $employeeIds = $employees->pluck('id')->map(fn ($id) => (int) $id)->all();
                if ($employeeIds === []) {
                    return;
                }

                $existingIds = $this->fetchExistingLoggedEmployeeIds($employeeIds, $hasLogsTable);
                $targetIds = array_values(array_filter($employeeIds, fn (int $id) => ! isset($existingIds[$id])));

                $payrollObservations = $hasPayrolls ? $this->fetchPayrollObservations($targetIds) : [];
                $approvedRequests = $hasIncreaseRequests ? $this->fetchApprovedRequests($targetIds) : [];

                $rowsToInsert = [];

                foreach ($employees as $employee) {
                    $stats['scanned']++;
                    $empId = (int) $employee->id;

                    if (isset($existingIds[$empId])) {
                        $stats['skipped_existing']++;

                        continue;
                    }

                    $timeline = EmployeeSalaryTimelineBuilder::forEmployee($employee)
                        ->withPayrollObservations($payrollObservations[$empId] ?? [])
                        ->withApprovedRequests($approvedRequests[$empId] ?? [])
                        ->reconciledAsOf($currentMonth)
                        ->build($now);

                    if ($timeline === []) {
                        $stats['skipped_zero']++;

                        continue;
                    }

                    $stats['backfilled']++;
                    if (count($timeline) > 1) {
                        $stats['with_multi_history']++;
                    }
                    $stats['logs_to_insert'] += count($timeline);
                    array_push($rowsToInsert, ...$timeline);
                }

                if (! $dryRun && $rowsToInsert !== []) {
                    DB::transaction(static function () use ($rowsToInsert): void {
                        foreach (array_chunk($rowsToInsert, 500) as $batch) {
                            DB::table('hr_employee_salary_logs')->insert($batch);
                        }
                    });
                }
            });

        return $stats;
    }

    /**
     * @param  array<int, int>  $employeeIds
     * @return array<int, int> Flipped map of employee_id => index
     */
    private function fetchExistingLoggedEmployeeIds(array $employeeIds, bool $hasLogsTable): array
    {
        if (! $hasLogsTable) {
            return [];
        }

        return DB::table('hr_employee_salary_logs')
            ->whereIn('employee_id', $employeeIds)
            ->distinct()
            ->pluck('employee_id')
            ->map(fn ($id) => (int) $id)
            ->flip()
            ->all();
    }

    /**
     * @param  array<int, int>  $targetEmployeeIds
     * @return array<int, array<string, float>>
     */
    private function fetchPayrollObservations(array $targetEmployeeIds): array
    {
        if ($targetEmployeeIds === []) {
            return [];
        }

        $txRows = DB::table('hr_salary_transactions as st')
            ->join('hr_payrolls as p', 'p.id', '=', 'st.payroll_id')
            ->whereIn('p.employee_id', $targetEmployeeIds)
            ->whereNull('p.deleted_at')
            ->where('p.status', '!=', Payroll::STATUS_CANCELLED)
            ->whereNull('st.deleted_at')
            ->where('st.type', 'salary')
            ->where('st.description', 'Base salary')
            ->where('st.amount', '>', 0)
            ->orderBy('p.year')
            ->orderBy('p.month')
            ->orderByDesc('st.id')
            ->get(['p.employee_id', 'p.year', 'p.month', 'st.amount']);

        $observations = [];
        foreach ($txRows as $tx) {
            $empId = (int) $tx->employee_id;
            $ym = sprintf('%04d-%02d-01', (int) $tx->year, (int) $tx->month);
            if (! isset($observations[$empId][$ym])) {
                $observations[$empId][$ym] = round((float) $tx->amount, 2);
            }
        }

        return $observations;
    }

    /**
     * @param  array<int, int>  $targetEmployeeIds
     * @return array<int, array<int, object>>
     */
    private function fetchApprovedRequests(array $targetEmployeeIds): array
    {
        if ($targetEmployeeIds === []) {
            return [];
        }

        $reqRows = DB::table('hr_salary_increase_requests')
            ->whereIn('employee_id', $targetEmployeeIds)
            ->where('status', SalaryIncreaseRequest::STATUS_APPROVED)
            ->whereNull('deleted_at')
            ->orderByRaw('COALESCE(effective_date, DATE(approved_at), DATE(created_at)) ASC')
            ->orderBy('id')
            ->get([
                'id',
                'employee_id',
                'old_salary',
                'new_salary',
                'effective_date',
                'approved_at',
                'approved_by',
                'notes',
                'created_at',
            ]);

        $grouped = [];
        foreach ($reqRows as $req) {
            $grouped[(int) $req->employee_id][] = $req;
        }

        return $grouped;
    }
}
