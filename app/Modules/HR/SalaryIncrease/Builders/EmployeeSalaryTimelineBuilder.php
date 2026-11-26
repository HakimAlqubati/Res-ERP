<?php

declare(strict_types=1);

namespace App\Modules\HR\SalaryIncrease\Builders;

use App\Models\Employee;
use App\Models\EmployeeSalaryLog;
use Carbon\Carbon;

/**
 * Fluent 3-layer historical salary timeline Builder used by `EmployeeSalaryBackfillService`.
 *
 * Layer 1: Compress historical full-month `Base salary` transactions into change points.
 * Layer 2: Merge approved `hr_salary_increase_requests` without duplicating matching points.
 * Layer 3: Reconcile any post-payroll delta against current `hr_employees.salary` without polluting prior months.
 */
final class EmployeeSalaryTimelineBuilder
{
    private Employee $employee;

    /** @var array<string, float> */
    private array $payrollObservations = [];

    /** @var array<int, object> */
    private array $approvedRequests = [];

    private Carbon $currentMonth;

    private function __construct(Employee $employee)
    {
        $this->employee = $employee;
        $this->currentMonth = now()->startOfMonth();
    }

    public static function forEmployee(Employee $employee): self
    {
        return new self($employee);
    }

    /**
     * @param  array<string, float>  $observations  Map of 'YYYY-MM-01' => observed monthly base salary
     */
    public function withPayrollObservations(array $observations): self
    {
        ksort($observations);

        $clone = clone $this;
        $clone->payrollObservations = $observations;

        return $clone;
    }

    /**
     * @param  array<int, object>  $requests  Approved salary increase request records ordered chronologically
     */
    public function withApprovedRequests(array $requests): self
    {
        $clone = clone $this;
        $clone->approvedRequests = $requests;

        return $clone;
    }

    public function reconciledAsOf(Carbon $currentMonth): self
    {
        $clone = clone $this;
        $clone->currentMonth = $currentMonth->copy()->startOfMonth();

        return $clone;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function build(?Carbon $timestamp = null): array
    {
        $joinDate = $this->employee->join_date ? Carbon::parse($this->employee->join_date) : null;
        $joinMonth = $joinDate?->copy()->startOfMonth();

        $events = $this->extractLayer1Events($joinDate, $joinMonth);
        $events = $this->mergeLayer2Requests($events, $joinDate, $joinMonth);
        $events = $this->sortEvents($events);
        $events = $this->applyLayer3Reconciliation($events, $joinDate, $joinMonth);
        $events = $this->sortEvents($events);

        return $this->finalizePayloads($events, $timestamp);
    }

    /**
     * Layer 1: Compress payroll base-salary observations into distinct change points.
     *
     * @return array<int, array<string, mixed>>
     */
    private function extractLayer1Events(?Carbon $joinDate, ?Carbon $joinMonth): array
    {
        $observations = $this->payrollObservations;
        ksort($observations);

        $events = [];
        $prevSalary = null;

        foreach ($observations as $ym => $salary) {
            if ($prevSalary === null) {
                $obsMonth = Carbon::parse($ym)->startOfMonth();
                $firstEffFrom = ($joinMonth !== null && $joinMonth->lt($obsMonth))
                    ? $joinMonth->toDateString()
                    : $obsMonth->toDateString();

                $events[] = [
                    'effective_from' => $firstEffFrom,
                    'effective_date' => ($joinDate && $joinMonth && $joinMonth->lte($obsMonth))
                        ? $joinDate->toDateString()
                        : $firstEffFrom,
                    'new_salary' => $salary,
                    'source' => EmployeeSalaryLog::SOURCE_BACKFILL,
                    'salary_increase_request_id' => null,
                    'notes' => 'Backfilled from historical payroll base salary.',
                    'created_by' => null,
                    'sort_order' => 1,
                ];
                $prevSalary = $salary;

                continue;
            }

            if (round($salary, 2) !== round($prevSalary, 2)) {
                $events[] = [
                    'effective_from' => $ym,
                    'effective_date' => $ym,
                    'new_salary' => $salary,
                    'source' => EmployeeSalaryLog::SOURCE_BACKFILL,
                    'salary_increase_request_id' => null,
                    'notes' => 'Backfilled salary change detected from historical payroll.',
                    'created_by' => null,
                    'sort_order' => 1,
                ];
                $prevSalary = $salary;
            }
        }

        return $events;
    }

    /**
     * Layer 2: Attach or append approved salary increase requests without duplicates.
     *
     * @param  array<int, array<string, mixed>>  $events
     * @return array<int, array<string, mixed>>
     */
    private function mergeLayer2Requests(array $events, ?Carbon $joinDate, ?Carbon $joinMonth): array
    {
        foreach ($this->approvedRequests as $req) {
            $reqDateStr = $req->effective_date
                ?? ($req->approved_at
                    ? Carbon::parse($req->approved_at)->toDateString()
                    : Carbon::parse($req->created_at)->toDateString());
            $reqMonth = Carbon::parse($reqDateStr)->startOfMonth()->toDateString();
            $reqNewSalary = round((float) $req->new_salary, 2);

            if ($this->attachRequestToMatchingEvent($events, $req, $reqMonth, $reqDateStr, $reqNewSalary)) {
                continue;
            }

            if ($events === [] && $req->old_salary !== null && round((float) $req->old_salary, 2) > 0) {
                $prevBeforeReq = Carbon::parse($reqMonth)->subMonth()->startOfMonth();
                $baselineMonth = ($joinMonth !== null && $joinMonth->lt($prevBeforeReq))
                    ? $joinMonth
                    : $prevBeforeReq;

                $useJoinDate = $joinMonth !== null && $joinMonth->lte($prevBeforeReq);

                $events[] = [
                    'effective_from' => $baselineMonth->toDateString(),
                    'effective_date' => ($useJoinDate && $joinDate) ? $joinDate->toDateString() : $baselineMonth->toDateString(),
                    'new_salary' => round((float) $req->old_salary, 2),
                    'source' => EmployeeSalaryLog::SOURCE_BACKFILL,
                    'salary_increase_request_id' => null,
                    'notes' => 'Backfilled baseline prior to approved salary increase request.',
                    'created_by' => null,
                    'sort_order' => 0,
                ];
            }

            $events[] = [
                'effective_from' => $reqMonth,
                'effective_date' => $reqDateStr,
                'new_salary' => $reqNewSalary,
                'source' => EmployeeSalaryLog::SOURCE_SALARY_INCREASE_REQUEST,
                'salary_increase_request_id' => (int) $req->id,
                'notes' => $req->notes,
                'created_by' => $req->approved_by ? (int) $req->approved_by : null,
                'sort_order' => 2,
            ];
        }

        return $events;
    }

    /**
     * @param  array<int, array<string, mixed>>  $events
     */
    private function attachRequestToMatchingEvent(
        array &$events,
        object $req,
        string $reqMonth,
        string $reqDateStr,
        float $reqNewSalary
    ): bool {
        foreach ($events as &$existingEvent) {
            if (
                $existingEvent['effective_from'] === $reqMonth
                && round((float) $existingEvent['new_salary'], 2) === $reqNewSalary
                && $existingEvent['salary_increase_request_id'] === null
            ) {
                $existingEvent['effective_date'] = $reqDateStr;
                $existingEvent['source'] = EmployeeSalaryLog::SOURCE_SALARY_INCREASE_REQUEST;
                $existingEvent['salary_increase_request_id'] = (int) $req->id;
                $existingEvent['notes'] = $req->notes ?: $existingEvent['notes'];
                $existingEvent['created_by'] = $req->approved_by ? (int) $req->approved_by : null;

                return true;
            }
        }

        return false;
    }

    /**
     * Layer 3: Reconcile timeline with current `hr_employees.salary`.
     *
     * @param  array<int, array<string, mixed>>  $events
     * @return array<int, array<string, mixed>>
     */
    private function applyLayer3Reconciliation(array $events, ?Carbon $joinDate, ?Carbon $joinMonth): array
    {
        $currentEmpSalary = $this->employee->salary !== null ? round((float) $this->employee->salary, 2) : null;

        if ($events === []) {
            if ($currentEmpSalary === null || $currentEmpSalary <= 0) {
                return [];
            }

            $effDate = $joinDate ?? ($this->employee->created_at ? Carbon::parse($this->employee->created_at) : now());
            $events[] = [
                'effective_from' => $effDate->copy()->startOfMonth()->toDateString(),
                'effective_date' => $effDate->toDateString(),
                'new_salary' => $currentEmpSalary,
                'source' => EmployeeSalaryLog::SOURCE_BACKFILL,
                'salary_increase_request_id' => null,
                'notes' => 'Backfilled from current employee salary.',
                'created_by' => null,
                'sort_order' => 3,
            ];

            return $events;
        }

        if ($currentEmpSalary === null) {
            return $events;
        }

        $currentMonthStr = $this->currentMonth->toDateString();
        $latestEffective = null;
        foreach ($events as $ev) {
            if ($ev['effective_from'] <= $currentMonthStr) {
                $latestEffective = $ev;
            }
        }

        if ($latestEffective !== null && round((float) $latestEffective['new_salary'], 2) === $currentEmpSalary) {
            return $events;
        }

        $reconcileMonth = $this->resolveReconciliationMonth($latestEffective, $joinMonth);
        $events[] = [
            'effective_from' => $reconcileMonth->toDateString(),
            'effective_date' => $reconcileMonth->toDateString(),
            'new_salary' => $currentEmpSalary,
            'source' => EmployeeSalaryLog::SOURCE_BACKFILL,
            'salary_increase_request_id' => null,
            'notes' => 'Reconciled with current hr_employees.salary.',
            'created_by' => null,
            'sort_order' => 4,
        ];

        return $events;
    }

    /**
     * @param  array<string, mixed>|null  $latestEffective
     */
    private function resolveReconciliationMonth(?array $latestEffective, ?Carbon $joinMonth): Carbon
    {
        $lastObservedMonth = $this->payrollObservations !== []
            ? Carbon::parse((string) array_key_last($this->payrollObservations))->startOfMonth()
            : ($latestEffective !== null
                ? Carbon::parse($latestEffective['effective_from'])->startOfMonth()
                : $this->currentMonth->copy()->subMonth());

        $nextAfterLast = $lastObservedMonth->copy()->addMonth()->startOfMonth();

        // Clamp joinMonth to currentMonth (defends against corrupt future join_date),
        // but NEVER clamp below nextAfterLast so existing/current-month payrolls are never polluted.
        $clampedJoinMonth = $joinMonth !== null
            ? ($joinMonth->gt($this->currentMonth) ? $this->currentMonth->copy() : $joinMonth->copy())
            : null;

        return ($clampedJoinMonth !== null && $clampedJoinMonth->gt($nextAfterLast))
            ? $clampedJoinMonth
            : $nextAfterLast;
    }

    /**
     * @param  array<int, array<string, mixed>>  $events
     * @return array<int, array<string, mixed>>
     */
    private function sortEvents(array $events): array
    {
        usort($events, static function (array $a, array $b): int {
            $cmp = strcmp($a['effective_from'], $b['effective_from']);

            return $cmp !== 0 ? $cmp : ($a['sort_order'] <=> $b['sort_order']);
        });

        return $events;
    }

    /**
     * Convert sorted events into canonical DB payloads using `EmployeeSalaryLogPayloadBuilder`.
     *
     * @param  array<int, array<string, mixed>>  $events
     * @return array<int, array<string, mixed>>
     */
    private function finalizePayloads(array $events, ?Carbon $timestamp): array
    {
        $rows = [];
        $runningSalary = null;

        foreach ($events as $ev) {
            $newSalary = round((float) $ev['new_salary'], 2);

            $payload = EmployeeSalaryLogPayloadBuilder::forEmployee((int) $this->employee->id)
                ->fromOldSalary($runningSalary)
                ->toNewSalary($newSalary)
                ->effectiveOn($ev['effective_date'], $ev['effective_from'])
                ->fromSource($ev['source'])
                ->linkedToRequest($ev['salary_increase_request_id'])
                ->withNotes($ev['notes'])
                ->byActor($ev['created_by'])
                ->build();

            if ($timestamp !== null) {
                $payload['created_at'] = $timestamp;
                $payload['updated_at'] = $timestamp;
            }

            $rows[] = $payload;
            $runningSalary = $newSalary;
        }

        return $rows;
    }
}
