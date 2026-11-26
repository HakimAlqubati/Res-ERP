<?php

declare(strict_types=1);

namespace App\Modules\HR\Payroll\Services;

use App\Models\Employee;
use App\Modules\HR\Payroll\Contracts\EmployeeSalaryResolverInterface;
use Illuminate\Support\Facades\DB;

/**
 * Single Source of Truth for resolving an employee's effective monthly contracted salary
 * for any (year, month) period from `hr_employee_salary_logs`.
 *
 * Resolution rules for target periodStart = 'YYYY-MM-01':
 *  1. Latest log with `effective_from <= periodStart` (ORDER BY effective_from DESC, id DESC).
 *  2. If none, check if employee has any log with `effective_from > periodStart`:
 *     - If yes, use earliest future log's `old_salary ?? 0.0` (do NOT leak current hr_employees.salary).
 *  3. If employee has NO logs at all in `hr_employee_salary_logs`, fall back to `hr_employees.salary ?? 0.0`.
 */
final class EmployeeSalaryResolver implements EmployeeSalaryResolverInterface
{
    public function resolveForPeriod(Employee|int $employee, int $year, int $month): float
    {
        $employeeId = $employee instanceof Employee ? (int) $employee->id : (int) $employee;

        if ($employeeId <= 0) {
            return 0.0;
        }

        $map = $this->resolveMapForPeriod([$employeeId], $year, $month);

        return $map[$employeeId] ?? 0.0;
    }

    /**
     * @param  array<int, int|string>  $employeeIds
     * @return array<int, float>
     */
    public function resolveMapForPeriod(array $employeeIds, int $year, int $month): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $employeeIds), fn (int $id) => $id > 0)));

        if ($ids === []) {
            return [];
        }

        $periodStart = sprintf('%04d-%02d-01', $year, $month);
        $result = [];

        // 1. Latest log at or before periodStart via MySQL 8 window function (zero N+1)
        $rankedSubquery = DB::table('hr_employee_salary_logs')
            ->select([
                'employee_id',
                'new_salary',
                DB::raw('ROW_NUMBER() OVER (PARTITION BY employee_id ORDER BY effective_from DESC, id DESC) as rn'),
            ])
            ->whereIn('employee_id', $ids)
            ->where('effective_from', '<=', $periodStart);

        $matched = DB::query()
            ->fromSub($rankedSubquery, 'ranked')
            ->where('rn', 1)
            ->get(['employee_id', 'new_salary']);

        foreach ($matched as $row) {
            $result[(int) $row->employee_id] = round((float) $row->new_salary, 2);
        }

        $missingIds = array_values(array_diff($ids, array_keys($result)));

        if ($missingIds === []) {
            return $result;
        }

        // 2. For employees with no log <= periodStart, inspect earliest future log
        $earliestSubquery = DB::table('hr_employee_salary_logs')
            ->select([
                'employee_id',
                'old_salary',
                DB::raw('ROW_NUMBER() OVER (PARTITION BY employee_id ORDER BY effective_from ASC, id ASC) as rn'),
            ])
            ->whereIn('employee_id', $missingIds)
            ->where('effective_from', '>', $periodStart);

        $earliestFuture = DB::query()
            ->fromSub($earliestSubquery, 'earliest')
            ->where('rn', 1)
            ->get(['employee_id', 'old_salary'])
            ->keyBy('employee_id');

        $stillMissingIds = [];

        foreach ($missingIds as $empId) {
            if ($earliestFuture->has($empId)) {
                $row = $earliestFuture->get($empId);
                $result[$empId] = round((float) ($row->old_salary ?? 0.0), 2);
            } else {
                $stillMissingIds[] = $empId;
            }
        }

        if ($stillMissingIds === []) {
            return $result;
        }

        // 3. Employees with zero logs at all fall back to hr_employees.salary
        $fallbacks = DB::table('hr_employees')
            ->whereIn('id', $stillMissingIds)
            ->pluck('salary', 'id');

        foreach ($stillMissingIds as $empId) {
            $result[$empId] = round((float) ($fallbacks[$empId] ?? 0.0), 2);
        }

        return $result;
    }

    /**
     * @param  array<string, array<int, int|string>>  $periodEmployeeMap
     * @return array<string, array<int, float>>
     */
    public function resolveMapForPeriods(array $periodEmployeeMap): array
    {
        $resolved = [];

        foreach ($periodEmployeeMap as $periodKey => $employeeIds) {
            if (! is_string($periodKey) || ! preg_match('/^(\d{4})-(\d{2})$/', $periodKey, $matches)) {
                continue;
            }

            $year = (int) $matches[1];
            $month = (int) $matches[2];

            $resolved[$periodKey] = $this->resolveMapForPeriod($employeeIds, $year, $month);
        }

        return $resolved;
    }

    /**
     * Convenience wrapper for queries against `hr_payrolls` where `(employee_id, year, month)` are in scope.
     */
    public static function payrollPeriodSalarySubquery(string $tableAlias = 'hr_payrolls'): string
    {
        return self::effectiveSalarySubquery(
            "{$tableAlias}.employee_id",
            "STR_TO_DATE(CONCAT({$tableAlias}.year, '-', LPAD({$tableAlias}.month, 2, '0'), '-01'), '%Y-%m-%d')"
        );
    }

    /**
     * Build a correlated SQL scalar expression that matches `resolveMapForPeriod()` semantics
     * and is safe under MySQL `ONLY_FULL_GROUP_BY` when `$employeeIdCol` and `$periodStartSql`
     * depend only on grouped columns or constants.
     *
     * IMPORTANT: Callers must pass trusted SQL column names / safe formatted date literals.
     */
    public static function effectiveSalarySubquery(string $employeeIdCol, string $periodStartSql): string
    {
        return "COALESCE(
            (
                SELECT esl.new_salary
                FROM hr_employee_salary_logs esl
                WHERE esl.employee_id = {$employeeIdCol}
                  AND esl.effective_from <= {$periodStartSql}
                ORDER BY esl.effective_from DESC, esl.id DESC
                LIMIT 1
            ),
            CASE
                WHEN EXISTS (
                    SELECT 1
                    FROM hr_employee_salary_logs esl_any
                    WHERE esl_any.employee_id = {$employeeIdCol}
                )
                THEN COALESCE(
                    (
                        SELECT esl_fut.old_salary
                        FROM hr_employee_salary_logs esl_fut
                        WHERE esl_fut.employee_id = {$employeeIdCol}
                          AND esl_fut.effective_from > {$periodStartSql}
                        ORDER BY esl_fut.effective_from ASC, esl_fut.id ASC
                        LIMIT 1
                    ),
                    0
                )
                ELSE COALESCE(
                    (
                        SELECT emp_fb.salary
                        FROM hr_employees emp_fb
                        WHERE emp_fb.id = {$employeeIdCol}
                        LIMIT 1
                    ),
                    0
                )
            END,
            0
        )";
    }
}
