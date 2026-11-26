<?php

declare(strict_types=1);

namespace Tests\Feature\HR;

use App\Enums\HR\Payroll\SalaryTransactionType;
use App\Exports\PayrollsExport;
use App\Imports\EmployeeImport;
use App\Models\Branch;
use App\Models\Deduction;
use App\Models\Employee;
use App\Models\EmployeeBranchLog;
use App\Models\EmployeeSalaryLog;
use App\Models\Payroll;
use App\Models\PayrollRun;
use App\Models\PenaltyDeduction;
use App\Models\SalaryIncreaseRequest;
use App\Models\SalaryTransaction;
use App\Models\User;
use App\Modules\HR\Employee\Services\EmployeeLifecycleService;
use App\Modules\HR\EmployeeApplications\Checker\MonthlyPendingApplicationChecker;
use App\Modules\HR\EmployeeApplications\Notifications\BranchManagerNotifier;
use App\Modules\HR\Payroll\Contracts\EmployeeSalaryResolverInterface;
use App\Modules\HR\Payroll\DTOs\RunPayrollData;
use App\Modules\HR\Payroll\Reports\SalarySlipReport;
use App\Modules\HR\Payroll\Services\EmployeeSalaryResolver;
use App\Modules\HR\Payroll\Services\PayrollRunService;
use App\Modules\HR\Payroll\Services\PayrollSimulationService;
use App\Modules\HR\SalaryIncrease\Builders\SalaryIncreasePayloadBuilder;
use App\Modules\HR\SalaryIncrease\Contracts\EmployeeSalaryLogServiceInterface;
use App\Modules\HR\SalaryIncrease\Contracts\SalaryIncreaseServiceInterface;
use App\Modules\HR\SalaryIncrease\DTOs\SalaryIncreaseDataDTO;
use App\Modules\HR\SalaryIncrease\Services\EmployeeSalaryBackfillService;
use App\Services\HR\BranchAttendanceSummaryService;
use App\Services\HR\Payroll\PayrollLockGuard;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class EmployeeSalaryLogsAndPayrollIsolationTest extends TestCase
{
    use DatabaseTransactions;

    private function createBranchAndManager(string $suffix): array
    {
        $managerUser = User::create([
            'name' => "Manager {$suffix}",
            'email' => "mgr_{$suffix}_".Str::random(8).'@test.local',
            'password' => bcrypt('secret123'),
            'active' => 1,
        ]);

        $branch = Branch::create([
            'name' => "Branch {$suffix} ".Str::random(5),
            'manager_id' => $managerUser->id,
            'active' => true,
            'type' => Branch::TYPE_BRANCH,
        ]);

        $managerUser->updateQuietly(['branch_id' => $branch->id]);

        return [$branch, $managerUser];
    }

    private function createTestEmployee(
        Branch $branch,
        float $salary = 3000.00,
        string $joinDate = '2025-01-01',
        bool $seedInitialLog = true,
        bool $seedBranchLog = true
    ): array {
        $user = User::create([
            'name' => 'Emp '.Str::random(6),
            'email' => 'emp_'.Str::random(10).'@test.local',
            'password' => bcrypt('secret123'),
            'branch_id' => $branch->id,
            'active' => 1,
        ]);

        $employee = Employee::withoutEvents(function () use ($user, $branch, $salary, $joinDate) {
            return Employee::create([
                'name' => 'Test Employee '.Str::random(6),
                'email' => $user->email,
                'user_id' => $user->id,
                'branch_id' => $branch->id,
                'active' => 1,
                'employment_cycle' => 1,
                'join_date' => $joinDate,
                'salary' => $salary,
                'working_hours' => 8,
                'working_days' => 26,
                'no_shift_is_present' => true,
                'has_auto_weekly_leave' => false,
                'discount_exception_if_absent' => true,
                'discount_exception_if_attendance_late' => true,
            ]);
        });

        if ($seedBranchLog) {
            EmployeeBranchLog::create([
                'employee_id' => $employee->id,
                'branch_id' => $branch->id,
                'start_at' => $joinDate.' 00:00:00',
                'end_at' => null,
            ]);
        }

        if ($seedInitialLog && $salary > 0) {
            app(EmployeeSalaryLogServiceInterface::class)->recordInitialSalary(
                $employee,
                $joinDate,
                EmployeeSalaryLog::SOURCE_INITIAL
            );
        }

        return [$employee, $user];
    }

    private function createPayrollRecord(
        Branch $branch,
        Employee $employee,
        int $year,
        int $month,
        float $baseSalary,
        string $status = Payroll::STATUS_APPROVED,
        bool $isPaid = false
    ): Payroll {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $run = PayrollRun::withoutEvents(function () use ($branch, $year, $month, $start, $end, $baseSalary) {
            return PayrollRun::create([
                'status' => PayrollRun::STATUS_APPROVED,
                'branch_id' => $branch->id,
                'year' => $year,
                'month' => $month,
                'period_start_date' => $start->toDateString(),
                'period_end_date' => $end->toDateString(),
                'name' => $start->format('M Y').' - '.$branch->name,
                'pay_date' => $end->toDateString(),
                'total_gross' => $baseSalary,
                'total_net' => $baseSalary,
                'total_allowances' => 0,
                'total_deductions' => 0,
            ]);
        });

        $payroll = Payroll::withoutEvents(function () use ($run, $branch, $employee, $year, $month, $start, $end, $baseSalary, $status, $isPaid) {
            return Payroll::create([
                'payroll_run_id' => $run->id,
                'employee_id' => $employee->id,
                'branch_id' => $branch->id,
                'year' => $year,
                'month' => $month,
                'period_start_date' => $start->toDateString(),
                'period_end_date' => $end->toDateString(),
                'base_salary' => $baseSalary,
                'gross_salary' => $baseSalary,
                'net_salary' => $baseSalary,
                'status' => $status,
                'is_paid' => $isPaid,
            ]);
        });

        SalaryTransaction::withoutEvents(function () use ($payroll, $run, $employee, $year, $month, $end, $baseSalary) {
            SalaryTransaction::create([
                'employee_id' => $employee->id,
                'payroll_id' => $payroll->id,
                'payroll_run_id' => $run->id,
                'year' => $year,
                'month' => $month,
                'date' => $end->toDateString(),
                'amount' => $baseSalary,
                'type' => SalaryTransactionType::TYPE_SALARY->value,
                'sub_type' => 'base_salary',
                'operation' => '+',
                'description' => 'Base salary',
                'status' => SalaryTransaction::STATUS_APPROVED,
            ]);
        });

        return $payroll;
    }

    public function test_qa_scenario_end_to_end_salary_increase_for_current_month_isolates_previous_month_payroll_export_and_slip(): void
    {
        [$branch, $managerUser] = $this->createBranchAndManager('QA');
        [$employee] = $this->createTestEmployee($branch, salary: 3000.00, joinDate: '2025-01-01', seedInitialLog: false);

        $this->actingAs($managerUser);

        $currentMonth = now()->copy()->startOfMonth();
        $prevMonth = $currentMonth->copy()->subMonth()->startOfMonth();

        // 1. Approve salary increase 3000 -> 3500 effective in current month M BEFORE M-1 payroll is created
        $increaseService = app(SalaryIncreaseServiceInterface::class);
        $request = $increaseService->create(SalaryIncreaseDataDTO::fromArray([
            'employee_id' => $employee->id,
            'increase_type' => SalaryIncreaseRequest::INCREASE_TYPE_FIXED,
            'increase_amount' => 500,
            'effective_date' => $currentMonth->copy()->addDays(4)->toDateString(),
            'notes' => 'QA increment for current month',
        ], $managerUser->id));

        $approved = $increaseService->approve($request->id, $managerUser->id);

        $this->assertSame(SalaryIncreaseRequest::STATUS_APPROVED, $approved->status);
        $this->assertEquals(3500.00, (float) $employee->fresh()->salary);

        // Verify Auto-Baseline + Increase log exist
        $logs = EmployeeSalaryLog::where('employee_id', $employee->id)
            ->orderBy('effective_from')
            ->orderBy('id')
            ->get();
        $this->assertCount(2, $logs);
        $this->assertSame(EmployeeSalaryLog::SOURCE_AUTO_BASELINE, $logs[0]->source);
        $this->assertEquals(3000.00, (float) $logs[0]->new_salary);
        $this->assertSame(EmployeeSalaryLog::SOURCE_SALARY_INCREASE_REQUEST, $logs[1]->source);
        $this->assertEquals(3500.00, (float) $logs[1]->new_salary);
        $this->assertSame($approved->id, (int) $logs[1]->salary_increase_request_id);

        // 2. Simulate & persist payroll for M-1 (previous month) — MUST use 3000, NOT 3500!
        $simulator = app(PayrollSimulationService::class);
        $simPrev = $simulator->simulateForEmployees([$employee->id], $prevMonth->year, $prevMonth->month, $branch->id);
        $this->assertNotEmpty($simPrev);
        $this->assertTrue($simPrev[0]['success']);
        $this->assertEquals(3000.00, (float) $simPrev[0]['monthly_salary']);

        $runService = app(PayrollRunService::class);
        $runPrevResult = $runService->runAndPersist(new RunPayrollData(
            branchId: $branch->id,
            year: $prevMonth->year,
            month: $prevMonth->month,
            employeeIds: [$employee->id],
        ));
        $this->assertTrue($runPrevResult['success']);

        $payrollPrev = Payroll::where('employee_id', $employee->id)
            ->where('year', $prevMonth->year)
            ->where('month', $prevMonth->month)
            ->firstOrFail();

        $this->assertEquals(3000.00, $payrollPrev->period_salary);

        // Mark M-1 approved & paid so M can be generated without triggering Unpaid Previous Payroll check
        Payroll::withoutEvents(function () use ($payrollPrev): void {
            $payrollPrev->update([
                'status' => Payroll::STATUS_APPROVED,
                'is_paid' => true,
            ]);
        });

        // 3. Simulate & persist payroll for M (current month) — MUST use 3500!
        $simCurr = $simulator->simulateForEmployees([$employee->id], $currentMonth->year, $currentMonth->month, $branch->id);
        $this->assertNotEmpty($simCurr);
        $this->assertTrue($simCurr[0]['success']);
        $this->assertEquals(3500.00, (float) $simCurr[0]['monthly_salary']);

        $runCurrResult = $runService->runAndPersist(new RunPayrollData(
            branchId: $branch->id,
            year: $currentMonth->year,
            month: $currentMonth->month,
            employeeIds: [$employee->id],
        ));
        $this->assertTrue($runCurrResult['success']);

        $payrollCurr = Payroll::where('employee_id', $employee->id)
            ->where('year', $currentMonth->year)
            ->where('month', $currentMonth->month)
            ->firstOrFail();

        $this->assertEquals(3500.00, $payrollCurr->period_salary);

        // 4. Verify PayrollsRelationManager correlated SQL subquery under GROUP BY
        $periodSalaryExpr = EmployeeSalaryResolver::effectiveSalarySubquery(
            'hr_payrolls.employee_id',
            "STR_TO_DATE(CONCAT(hr_payrolls.year, '-', LPAD(hr_payrolls.month, 2, '0'), '-01'), '%Y-%m-%d')"
        );

        $groupedRows = Payroll::query()
            ->whereIn('hr_payrolls.id', [$payrollPrev->id, $payrollCurr->id])
            ->selectRaw("hr_payrolls.payroll_run_id, hr_payrolls.employee_id, hr_payrolls.year, hr_payrolls.month, ({$periodSalaryExpr}) as period_salary")
            ->groupBy('hr_payrolls.payroll_run_id', 'hr_payrolls.employee_id', 'hr_payrolls.year', 'hr_payrolls.month')
            ->orderBy('hr_payrolls.year')
            ->orderBy('hr_payrolls.month')
            ->get();

        $this->assertCount(2, $groupedRows);
        $this->assertEquals(3000.00, (float) $groupedRows[0]->getAttribute('period_salary'));
        $this->assertEquals(3500.00, (float) $groupedRows[1]->getAttribute('period_salary'));

        // 5. Verify PayrollsExport (multi-month bulk export) resolves 3000 for M-1 and 3500 for M
        $payrollsForExport = Payroll::query()
            ->whereIn('id', [$payrollPrev->id, $payrollCurr->id])
            ->orderBy('year')
            ->orderBy('month')
            ->get();
        $exportView = (new PayrollsExport($payrollsForExport))->view();
        $exportRows = $exportView->getData()['rows'];
        $this->assertCount(2, $exportRows);
        $this->assertEquals(3000.00, (float) $exportRows[0]['base_salary']);
        $this->assertEquals(3500.00, (float) $exportRows[1]['base_salary']);

        // 6. Verify SalarySlipReport (data + JSON + Blade PDF view) returns 3000 for M-1 and 3500 for M
        $slipReport = app(SalarySlipReport::class);
        $prevSlipData = $slipReport->getData($payrollPrev->id);
        $currSlipData = $slipReport->getData($payrollCurr->id);

        $this->assertEquals(3000.00, (float) $prevSlipData['contracted_salary']);
        $this->assertEquals(3500.00, (float) $currSlipData['contracted_salary']);

        $prevJson = $slipReport->json($payrollPrev->id)->getData(true);
        $this->assertTrue($prevJson['success']);
        $this->assertEquals(3000.0, (float) $prevJson['data']['contracted_salary']);

        $prevHtml = view('reports.hr.payroll.salary-slip-pdf', $prevSlipData)->render();
        $this->assertStringContainsString('3,000', $prevHtml);
    }

    public function test_direct_salary_edit_without_request_isolates_previous_month_and_handles_is_dirty_trap(): void
    {
        [$branch, $managerUser] = $this->createBranchAndManager('DIR');
        [$employee] = $this->createTestEmployee($branch, salary: 3000.00, joinDate: '2025-01-01');

        $this->actingAs($managerUser);

        $currentMonth = now()->copy()->startOfMonth();
        $prevMonth = $currentMonth->copy()->subMonth()->startOfMonth();

        $logService = app(EmployeeSalaryLogServiceInterface::class);
        $resolver = app(EmployeeSalaryResolverInterface::class);

        // 1. Direct salary increase without a request
        $logService->recordSalaryChange(
            employee: $employee,
            newSalary: 3600.00,
            effectiveDate: $currentMonth->toDateString(),
            source: EmployeeSalaryLog::SOURCE_DIRECT_EDIT,
            notes: 'Direct adjustment up'
        );

        $this->assertEquals(3000.00, $resolver->resolveForPeriod($employee, $prevMonth->year, $prevMonth->month));
        $this->assertEquals(3600.00, $resolver->resolveForPeriod($employee, $currentMonth->year, $currentMonth->month));

        // 2. Direct salary decrease in the same unlocked month
        $logService->recordSalaryChange(
            employee: $employee,
            newSalary: 3200.00,
            effectiveDate: $currentMonth->toDateString(),
            source: EmployeeSalaryLog::SOURCE_DIRECT_EDIT,
            notes: 'Direct adjustment down'
        );

        $this->assertEquals(3000.00, $resolver->resolveForPeriod($employee, $prevMonth->year, $prevMonth->month));
        $this->assertEquals(3200.00, $resolver->resolveForPeriod($employee, $currentMonth->year, $currentMonth->month));
        $this->assertEquals(3200.00, (float) $employee->fresh()->salary);

        // 3. Lock current month with a payroll; verify isDirty('salary') trap ("3200.00" vs 3200) does NOT block phone update
        $this->createPayrollRecord($branch, $employee, $currentMonth->year, $currentMonth->month, 3200.00);

        $logCountBefore = EmployeeSalaryLog::where('employee_id', $employee->id)->count();

        $employee->refresh();
        $employee->phone_number = '+60123456789';
        $employee->salary = 3200; // int vs decimal string "3200.00"
        $employee->save();

        $this->assertSame('+60123456789', $employee->fresh()->phone_number);
        $this->assertSame($logCountBefore, EmployeeSalaryLog::where('employee_id', $employee->id)->count());

        // 4. Attempting a genuine direct programmatic salary change on a locked month MUST throw ValidationException
        try {
            $employee->refresh();
            $employee->update(['salary' => 4500.00]);
            $this->fail('Expected ValidationException when directly editing salary on a locked month.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('salary', $e->errors());
        }

        $this->assertEquals(3200.00, (float) $employee->fresh()->salary);
    }

    public function test_forward_payroll_lock_guard_blocks_locked_month_empty_prior_month_and_rechecks_on_approve(): void
    {
        [$branch, $managerUser] = $this->createBranchAndManager('LOCK');
        [$employee] = $this->createTestEmployee($branch, salary: 3000.00, joinDate: '2025-01-01');

        $this->actingAs($managerUser);

        $currentMonth = now()->copy()->startOfMonth();
        $prevMonth = $currentMonth->copy()->subMonth()->startOfMonth();
        $nextMonth = $currentMonth->copy()->addMonth()->startOfMonth();

        $lockGuard = app(PayrollLockGuard::class);
        $increaseService = app(SalaryIncreaseServiceInterface::class);

        // 1. Create payroll in M (current month), leaving M-1 empty
        $payrollCurr = $this->createPayrollRecord($branch, $employee, $currentMonth->year, $currentMonth->month, 3000.00);

        $this->assertTrue($lockGuard->isLockedFromPeriod($employee->id, $currentMonth->year, $currentMonth->month));
        $this->assertTrue($lockGuard->isLockedFromPeriod($employee->id, $prevMonth->year, $prevMonth->month));
        $this->assertFalse($lockGuard->isLockedFromPeriod($employee->id, $nextMonth->year, $nextMonth->month));

        // 2. Creating a salary increase request in M-1 (empty month BEFORE locked M) MUST be blocked!
        try {
            $increaseService->create(SalaryIncreaseDataDTO::fromArray([
                'employee_id' => $employee->id,
                'increase_type' => SalaryIncreaseRequest::INCREASE_TYPE_FIXED,
                'increase_amount' => 400,
                'effective_date' => $prevMonth->copy()->addDays(5)->toDateString(),
            ], $managerUser->id));
            $this->fail('Expected ValidationException for empty month prior to a locked payroll.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('effective_date', $e->errors());
        }

        // 3. Create pending request for M+1 (currently unlocked)
        $pendingNext = $increaseService->create(SalaryIncreaseDataDTO::fromArray([
            'employee_id' => $employee->id,
            'increase_type' => SalaryIncreaseRequest::INCREASE_TYPE_FIXED,
            'increase_amount' => 500,
            'effective_date' => $nextMonth->copy()->addDays(2)->toDateString(),
        ], $managerUser->id));

        // Now create a payroll for M+1 BEFORE approving the pending request -> approve() MUST re-check and reject!
        $payrollNext = $this->createPayrollRecord($branch, $employee, $nextMonth->year, $nextMonth->month, 3000.00);

        try {
            $increaseService->approve($pendingNext->id, $managerUser->id);
            $this->fail('Expected ValidationException when approving request after payroll was created.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('effective_date', $e->errors());
        }

        // 4. Cancelling M+1 payroll unlocks M+1 so approval now succeeds
        Payroll::withoutEvents(fn () => $payrollNext->update(['status' => Payroll::STATUS_CANCELLED]));
        $approvedNext = $increaseService->approve($pendingNext->id, $managerUser->id);
        $this->assertSame(SalaryIncreaseRequest::STATUS_APPROVED, $approvedNext->status);

        // 5. Approved request cannot be deleted via SalaryIncreaseRequestObserver
        try {
            $approvedNext->delete();
            $this->fail('Expected ValidationException when deleting an approved salary increase request.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('status', $e->errors());
        }

        // 6. Log linked to approved request cannot be deleted via EmployeeSalaryLogObserver
        $linkedLog = EmployeeSalaryLog::where('salary_increase_request_id', $approvedNext->id)->firstOrFail();
        try {
            $linkedLog->delete();
            $this->fail('Expected ValidationException when deleting a salary log linked to an approved request.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('salary_increase_request_id', $e->errors());
        }

        // 7. Cancelled payroll does not block checkLockFromPeriod, while active payroll does
        $this->assertNotNull($payrollCurr);
    }

    public function test_monthly_pending_application_checker_for_salary_increase_requests_and_branch_manager_notifier(): void
    {
        [$branchA, $managerUserA] = $this->createBranchAndManager('PA');
        [$branchB] = $this->createBranchAndManager('PB');
        [$employee] = $this->createTestEmployee($branchA, salary: 3000.00, joinDate: '2025-01-01', seedBranchLog: false);

        $this->actingAs($managerUserA);

        $currentMonth = now()->copy()->startOfMonth();
        $prevMonth = $currentMonth->copy()->subMonth()->startOfMonth();
        $nextMonth = $currentMonth->copy()->addMonth()->startOfMonth();

        // Employee was in Branch A during first half of currentMonth, transferred to Branch B mid-month
        EmployeeBranchLog::create([
            'employee_id' => $employee->id,
            'branch_id' => $branchA->id,
            'start_at' => '2025-01-01 00:00:00',
            'end_at' => $currentMonth->copy()->addDays(10)->toDateTimeString(),
        ]);
        EmployeeBranchLog::create([
            'employee_id' => $employee->id,
            'branch_id' => $branchB->id,
            'start_at' => $currentMonth->copy()->addDays(11)->toDateTimeString(),
            'end_at' => null,
        ]);
        $employee->updateQuietly(['branch_id' => $branchB->id]);

        // Pending salary increase request dated in M (stored under current branch B)
        app(SalaryIncreaseServiceInterface::class)->create(SalaryIncreaseDataDTO::fromArray([
            'employee_id' => $employee->id,
            'increase_type' => SalaryIncreaseRequest::INCREASE_TYPE_FIXED,
            'increase_amount' => 300,
            'effective_date' => $currentMonth->copy()->addDays(12)->toDateString(),
        ], $managerUserA->id));

        $checker = app(MonthlyPendingApplicationChecker::class);

        // M-1: NOT blocked by a request effective in M
        $this->assertFalse($checker->check([
            'year' => $prevMonth->year,
            'month' => $prevMonth->month,
            'employee_ids' => [$employee->id],
        ]));

        // M: BLOCKED for Branch B AND for Branch A (due to transfer during M!)
        $summaryMBranchB = $checker->getDashboardSummary([
            'year' => $currentMonth->year,
            'month' => $currentMonth->month,
            'branch_id' => $branchB->id,
        ]);
        $this->assertTrue($summaryMBranchB['has_pending']);
        $this->assertSame('Salary Increase Request', $summaryMBranchB['breakdown'][0]['type']);

        $this->assertTrue($checker->check([
            'year' => $currentMonth->year,
            'month' => $currentMonth->month,
            'branch_id' => $branchA->id,
        ]));

        // M+1: BLOCKED (unresolved pending request from M still blocks M+1)
        $this->assertTrue($checker->check([
            'year' => $nextMonth->year,
            'month' => $nextMonth->month,
            'employee_ids' => [$employee->id],
        ]));

        // BranchManagerNotifier MUST ignore Salary Increase Requests (returns 0 when no other pending items)
        $notifiedCount = app(BranchManagerNotifier::class)->notifyIfPending($branchB);
        $this->assertSame(0, $notifiedCount);

        // Soft-deleted employee MUST be excluded from pending salary increase checks
        $employee->delete();
        $this->assertFalse($checker->check([
            'year' => $currentMonth->year,
            'month' => $currentMonth->month,
            'employee_ids' => [$employee->id],
        ]));
    }

    public function test_future_salary_increase_deferral_sync_command_and_chained_future_payload_builder(): void
    {
        [$branch, $managerUser] = $this->createBranchAndManager('FUT');
        [$employee] = $this->createTestEmployee($branch, salary: 3000.00, joinDate: '2025-01-01');

        $this->actingAs($managerUser);

        $currentMonth = now()->copy()->startOfMonth();
        $nextMonth = $currentMonth->copy()->addMonth()->startOfMonth();
        $monthPlusTwo = $currentMonth->copy()->addMonths(2)->startOfMonth();

        $increaseService = app(SalaryIncreaseServiceInterface::class);
        $resolver = app(EmployeeSalaryResolverInterface::class);

        // 1. Approve future increase 3000 -> 3500 effective M+1
        $reqNext = $increaseService->create(SalaryIncreaseDataDTO::fromArray([
            'employee_id' => $employee->id,
            'increase_type' => SalaryIncreaseRequest::INCREASE_TYPE_FIXED,
            'increase_amount' => 500,
            'effective_date' => $nextMonth->copy()->addDays(2)->toDateString(),
        ], $managerUser->id));

        $increaseService->approve($reqNext->id, $managerUser->id);

        // hr_employees.salary cache MUST stay at 3000 until M+1 arrives
        $this->assertEquals(3000.00, (float) $employee->fresh()->salary);
        $this->assertEquals(3000.00, $resolver->resolveForPeriod($employee, $currentMonth->year, $currentMonth->month));
        $this->assertEquals(3500.00, $resolver->resolveForPeriod($employee, $nextMonth->year, $nextMonth->month));

        // 2. SalaryIncreasePayloadBuilder for M+2 MUST compute old_salary = 3500 (from M+1), NOT 3000!
        $payloadM2 = SalaryIncreasePayloadBuilder::make()
            ->forEmployee($employee->fresh())
            ->withData(SalaryIncreaseDataDTO::fromArray([
                'employee_id' => $employee->id,
                'increase_type' => SalaryIncreaseRequest::INCREASE_TYPE_FIXED,
                'increase_amount' => 500,
                'effective_date' => $monthPlusTwo->toDateString(),
            ], $managerUser->id))
            ->buildCreatePayload();

        $this->assertEquals(3500.00, (float) $payloadM2['old_salary']);
        $this->assertEquals(4000.00, (float) $payloadM2['new_salary']);

        // 3. Chronological month ordering blocks inserting a change in M while M+1 log exists
        try {
            app(EmployeeSalaryLogServiceInterface::class)->recordSalaryChange(
                employee: $employee,
                newSalary: 3200.00,
                effectiveDate: $currentMonth->toDateString(),
                source: EmployeeSalaryLog::SOURCE_DIRECT_EDIT,
            );
            $this->fail('Expected ValidationException when inserting earlier month than latest salary log.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('effective_date', $e->errors());
        }

        // 4. Sync service for M+1 updates hr_employees.salary cache to 3500
        $syncResult = app(EmployeeSalaryLogServiceInterface::class)->syncCurrentEffectiveSalaries($nextMonth);
        $this->assertGreaterThanOrEqual(1, $syncResult['synced']);
        $this->assertSame(0, $syncResult['failed']);
        $this->assertEquals(3500.00, (float) $employee->fresh()->salary);
    }

    public function test_scope_eligible_for_payroll_penalty_deduction_employee_import_and_rehire(): void
    {
        [$branch, $managerUser] = $this->createBranchAndManager('INT');
        $this->actingAs($managerUser);

        $currentMonth = now()->copy()->startOfMonth();
        $prevMonth = $currentMonth->copy()->subMonth()->startOfMonth();

        // 1. scopeEligibleForPayroll: employee whose initial salary log starts in M (no salary in M-1)
        [$empStartedM] = $this->createTestEmployee($branch, salary: 3000.00, joinDate: '2025-01-01', seedInitialLog: false);
        EmployeeSalaryLog::create([
            'employee_id' => $empStartedM->id,
            'old_salary' => null,
            'new_salary' => 3000.00,
            'change_amount' => null,
            'effective_from' => $currentMonth->toDateString(),
            'effective_date' => $currentMonth->toDateString(),
            'source' => EmployeeSalaryLog::SOURCE_INITIAL,
        ]);

        $this->assertFalse(
            Employee::query()->where('id', $empStartedM->id)->eligibleForPayroll($prevMonth->year, $prevMonth->month)->exists()
        );
        $this->assertTrue(
            Employee::query()->where('id', $empStartedM->id)->eligibleForPayroll($currentMonth->year, $currentMonth->month)->exists()
        );

        // 2. PenaltyDeductionObserver percentage calculation uses period-effective salary
        [$empPenalty] = $this->createTestEmployee($branch, salary: 3000.00, joinDate: '2025-01-01');
        app(EmployeeSalaryLogServiceInterface::class)->recordSalaryChange(
            employee: $empPenalty,
            newSalary: 4000.00,
            effectiveDate: $currentMonth->toDateString(),
            source: EmployeeSalaryLog::SOURCE_DIRECT_EDIT,
        );

        $deduction = Deduction::create([
            'name' => 'Test Penalty '.Str::random(5),
            'is_penalty' => true,
            'is_percentage' => true,
            'percentage' => 10,
            'active' => true,
        ]);

        $penalty = PenaltyDeduction::create([
            'branch_id' => $branch->id,
            'employee_id' => $empPenalty->id,
            'deduction_id' => $deduction->id,
            'deduction_type' => PenaltyDeduction::DEDUCTION_TYPE_SPECIFIC_PERCENTAGE,
            'percentage' => 10,
            'penalty_amount' => 999, // observer must recompute from M-1 effective salary (3000)
            'date' => $prevMonth->copy()->addDays(10)->toDateString(),
            'description' => 'Late penalty in M-1',
            'status' => PenaltyDeduction::STATUS_PENDING,
        ]);

        $this->assertEquals(300.00, (float) $penalty->fresh()->penalty_amount);

        // Moving penalty date to M recomputes against M effective salary (4000 -> 400)
        $penalty->update(['date' => $currentMonth->copy()->addDays(2)->toDateString()]);
        $this->assertEquals(400.00, (float) $penalty->fresh()->penalty_amount);

        // 3. EmployeeImport (withoutEvents) records initial salary log with source = 'import'
        $importEmail = 'imported_'.Str::random(8).'@test.local';
        $importer = new EmployeeImport;
        $importedEmployee = $importer->model([
            'name' => 'Imported Staff '.Str::random(4),
            'email' => $importEmail,
            'salary' => 2800,
            'branch_id' => $branch->id,
            'gender' => 'male',
            'join_date' => '2026-02-15',
            'working_days' => 26,
            'working_hours' => 8,
        ]);

        $this->assertInstanceOf(
            Employee::class,
            $importedEmployee,
            'EmployeeImport errors: '.json_encode($importer->getImportErrors())
        );
        $importLog = EmployeeSalaryLog::where('employee_id', $importedEmployee->id)->first();
        $this->assertNotNull($importLog);
        $this->assertSame(EmployeeSalaryLog::SOURCE_IMPORT, $importLog->source);
        $this->assertEquals(2800.00, (float) $importLog->new_salary);
        $this->assertSame('2026-02-01', $importLog->effective_from->toDateString());

        // 4. Rehire with new salary logs source = 'rehire' and preserves previous month salary
        [$empRehire] = $this->createTestEmployee($branch, salary: 3000.00, joinDate: '2025-01-01');
        $lifecycle = app(EmployeeLifecycleService::class);
        $term = $lifecycle->requestTermination($empRehire, [
            'termination_date' => $prevMonth->copy()->endOfMonth()->toDateString(),
            'termination_reason' => 'Resigned',
        ]);
        $lifecycle->approveTermination($term, $managerUser->id);

        $lifecycle->rehire($empRehire->fresh(), [
            'join_date' => $currentMonth->copy()->addDays(1)->toDateString(),
            'salary' => 3800.00,
            'notes' => 'Rehired with higher salary',
        ]);

        $rehireLog = EmployeeSalaryLog::where('employee_id', $empRehire->id)
            ->where('source', EmployeeSalaryLog::SOURCE_REHIRE)
            ->first();
        $this->assertNotNull($rehireLog);
        $this->assertEquals(3000.00, (float) $rehireLog->old_salary);
        $this->assertEquals(3800.00, (float) $rehireLog->new_salary);
        $this->assertEquals(3000.00, app(EmployeeSalaryResolverInterface::class)->resolveForPeriod($empRehire, $prevMonth->year, $prevMonth->month));
        $this->assertEquals(3800.00, app(EmployeeSalaryResolverInterface::class)->resolveForPeriod($empRehire, $currentMonth->year, $currentMonth->month));
    }

    public function test_backfill_command_three_layers_and_idempotency(): void
    {
        [$branch, $managerUser] = $this->createBranchAndManager('BF');
        $this->actingAs($managerUser);

        // Employee with NO logs yet, join_date = 2026-01-10, current salary = 4500 (modified after June payroll!)
        [$employee] = $this->createTestEmployee($branch, salary: 4500.00, joinDate: '2026-01-10', seedInitialLog: false);

        // Layer 1 historical payrolls (Base salary transactions):
        // Mar 2026: 3000, Apr 2026: 3000, May 2026: 3500 (direct edit), Jun 2026: 4000 (matches request)
        $this->createPayrollRecord($branch, $employee, 2026, 3, 3000.00, Payroll::STATUS_PAID, true);
        $this->createPayrollRecord($branch, $employee, 2026, 4, 3000.00, Payroll::STATUS_PAID, true);
        $this->createPayrollRecord($branch, $employee, 2026, 5, 3500.00, Payroll::STATUS_PAID, true);
        $this->createPayrollRecord($branch, $employee, 2026, 6, 4000.00, Payroll::STATUS_PAID, true);

        // Layer 2 approved salary increase request for Jun 2026 (3500 -> 4000)
        $approvedReq = SalaryIncreaseRequest::withoutEvents(function () use ($employee, $branch, $managerUser) {
            return SalaryIncreaseRequest::create([
                'employee_id' => $employee->id,
                'branch_id' => $branch->id,
                'old_salary' => 3500.00,
                'increase_type' => SalaryIncreaseRequest::INCREASE_TYPE_FIXED,
                'increase_amount' => 500.00,
                'new_salary' => 4000.00,
                'effective_date' => '2026-06-15',
                'status' => SalaryIncreaseRequest::STATUS_APPROVED,
                'approved_by' => $managerUser->id,
                'approved_at' => '2026-06-15 10:00:00',
                'notes' => 'Mid-year promotion',
            ]);
        });

        // 1. Dry-run inserts nothing (invoking EmployeeSalaryBackfillService directly avoids touching live tenants)
        $backfillService = app(EmployeeSalaryBackfillService::class);
        $dryStats = $backfillService->run(dryRun: true, chunkSize: 500);
        $this->assertNull($dryStats['skipped_reason']);
        $this->assertSame(0, EmployeeSalaryLog::where('employee_id', $employee->id)->count());

        // 2. Real run builds exact 4-point 3-layer timeline
        $backfillService->run(dryRun: false, chunkSize: 500);

        $logs = EmployeeSalaryLog::where('employee_id', $employee->id)
            ->orderBy('effective_from')
            ->orderBy('id')
            ->get();

        $this->assertCount(4, $logs);

        // Entry 1: baseline at join_date month (2026-01-01) = 3000
        $this->assertSame('2026-01-01', $logs[0]->effective_from->toDateString());
        $this->assertEquals(3000.00, (float) $logs[0]->new_salary);
        $this->assertNull($logs[0]->old_salary);

        // Entry 2: May 2026 change detected from payroll = 3500
        $this->assertSame('2026-05-01', $logs[1]->effective_from->toDateString());
        $this->assertEquals(3000.00, (float) $logs[1]->old_salary);
        $this->assertEquals(3500.00, (float) $logs[1]->new_salary);

        // Entry 3: Jun 2026 merged with approved request = 4000
        $this->assertSame('2026-06-01', $logs[2]->effective_from->toDateString());
        $this->assertEquals(3500.00, (float) $logs[2]->old_salary);
        $this->assertEquals(4000.00, (float) $logs[2]->new_salary);
        $this->assertSame(EmployeeSalaryLog::SOURCE_SALARY_INCREASE_REQUEST, $logs[2]->source);
        $this->assertSame($approvedReq->id, (int) $logs[2]->salary_increase_request_id);

        // Entry 4: Layer 3 reconciliation to 4500 at 2026-07-01 (does NOT pollute June!)
        $this->assertSame('2026-07-01', $logs[3]->effective_from->toDateString());
        $this->assertEquals(4000.00, (float) $logs[3]->old_salary);
        $this->assertEquals(4500.00, (float) $logs[3]->new_salary);

        // Verify resolver across all historical months
        $resolver = app(EmployeeSalaryResolverInterface::class);
        $this->assertEquals(3000.00, $resolver->resolveForPeriod($employee, 2026, 3));
        $this->assertEquals(3000.00, $resolver->resolveForPeriod($employee, 2026, 4));
        $this->assertEquals(3500.00, $resolver->resolveForPeriod($employee, 2026, 5));
        $this->assertEquals(4000.00, $resolver->resolveForPeriod($employee, 2026, 6));
        $this->assertEquals(4500.00, $resolver->resolveForPeriod($employee, 2026, 7));

        // 3. Re-run MUST be 100% idempotent (zero duplicate rows!)
        $totalLogsBefore = DB::table('hr_employee_salary_logs')->count();
        $backfillService->run(dryRun: false, chunkSize: 500);
        $this->assertSame($totalLogsBefore, DB::table('hr_employee_salary_logs')->count());
        $this->assertCount(4, EmployeeSalaryLog::where('employee_id', $employee->id)->get());
    }

    public function test_branch_attendance_summary_and_log_observer_unlocked_future_delete_and_created_observer(): void
    {
        [$branch, $managerUser] = $this->createBranchAndManager('SUM');
        $this->actingAs($managerUser);

        $currentMonth = now()->copy()->startOfMonth();
        $prevMonth = $currentMonth->copy()->subMonth()->startOfMonth();
        $nextMonth = $currentMonth->copy()->addMonth()->startOfMonth();

        // 1. Employee created WITH events (no withoutEvents) auto-creates SOURCE_INITIAL log
        $empUser = User::create([
            'name' => 'Observed Emp',
            'email' => 'obs_emp_'.Str::random(8).'@test.local',
            'password' => bcrypt('secret123'),
            'branch_id' => $branch->id,
            'active' => 1,
        ]);

        $employee = Employee::create([
            'name' => 'Observed Employee '.Str::random(5),
            'email' => $empUser->email,
            'user_id' => $empUser->id,
            'branch_id' => $branch->id,
            'active' => 1,
            'employment_cycle' => 1,
            'join_date' => '2025-01-15',
            'salary' => 3000.00,
            'working_hours' => 8,
            'working_days' => 26,
            'no_shift_is_present' => true,
            'has_auto_weekly_leave' => false,
        ]);

        $initialLog = EmployeeSalaryLog::where('employee_id', $employee->id)->first();
        $this->assertNotNull($initialLog);
        $this->assertSame(EmployeeSalaryLog::SOURCE_INITIAL, $initialLog->source);
        $this->assertSame('2025-01-01', $initialLog->effective_from->toDateString());
        $this->assertEquals(3000.00, (float) $initialLog->new_salary);

        // Increase to 3500 effective in M
        app(EmployeeSalaryLogServiceInterface::class)->recordSalaryChange(
            employee: $employee,
            newSalary: 3500.00,
            effectiveDate: $currentMonth->toDateString(),
            source: EmployeeSalaryLog::SOURCE_DIRECT_EDIT,
        );

        // 2. BranchAttendanceSummaryService resolves 3000 for M-1 and 3500 for M
        $summaryService = app(BranchAttendanceSummaryService::class);
        $prevSummary = $summaryService->generate($branch->id, $prevMonth->year, $prevMonth->month);
        $currSummary = $summaryService->generate($branch->id, $currentMonth->year, $currentMonth->month);

        $prevRow = collect($prevSummary['current_staff'])->firstWhere('employee_id', $employee->id);
        $currRow = collect($currSummary['current_staff'])->firstWhere('employee_id', $employee->id);

        $this->assertNotNull($prevRow);
        $this->assertNotNull($currRow);
        $this->assertEquals(3000.00, (float) $prevRow['salary']);
        $this->assertEquals(3500.00, (float) $currRow['salary']);

        // 3. Future unlocked direct_edit log CAN be deleted, while locked log CANNOT
        $futureLog = app(EmployeeSalaryLogServiceInterface::class)->recordSalaryChange(
            employee: $employee,
            newSalary: 4200.00,
            effectiveDate: $nextMonth->toDateString(),
            source: EmployeeSalaryLog::SOURCE_DIRECT_EDIT,
        );

        $this->assertTrue((bool) $futureLog->delete());
        $this->assertEquals(3500.00, (float) $employee->fresh()->salary);

        // Lock M with a payroll -> deleting M's log MUST throw ValidationException
        $this->createPayrollRecord($branch, $employee, $currentMonth->year, $currentMonth->month, 3500.00);
        $currentLog = EmployeeSalaryLog::where('employee_id', $employee->id)
            ->where('effective_from', $currentMonth->toDateString())
            ->firstOrFail();

        $this->expectException(ValidationException::class);
        $currentLog->delete();
    }
}
