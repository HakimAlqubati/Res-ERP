<?php

declare(strict_types=1);

namespace Tests\Feature\HR;

use App\Enums\HR\Payroll\SalaryTransactionType;
use App\Filament\Clusters\HRCluster\Resources\EmployeeResource\RelationManagers\SalaryLogRelationManager;
use App\Filament\Clusters\HRSalaryCluster\Resources\PayrollResource\Pages\ViewPayroll;
use App\Filament\Clusters\HRSalaryCluster\Resources\PayrollResource\RelationManagers\PayrollsRelationManager;
use App\Filament\Clusters\HRSalaryCluster\Resources\SalaryIncreaseRequests\Pages\CreateSalaryIncreaseRequest;
use App\Filament\Clusters\HRSalaryCluster\Resources\SalaryIncreaseRequests\Pages\EditSalaryIncreaseRequest;
use App\Filament\Clusters\HRSalaryCluster\Resources\SalaryIncreaseRequests\SalaryIncreaseRequestResource;
use App\Filament\Resources\EmployeeResource;
use App\Filament\Resources\EmployeeResource\Pages\EditEmployee;
use App\Filament\Resources\EmployeeResource\Pages\ViewEmployee;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeBranchLog;
use App\Models\EmployeeSalaryLog;
use App\Models\Payroll;
use App\Models\PayrollRun;
use App\Models\SalaryIncreaseRequest;
use App\Models\SalaryTransaction;
use App\Models\User;
use App\Modules\HR\EmployeeApplications\Checker\MonthlyPendingApplicationChecker;
use App\Modules\HR\SalaryIncrease\Builders\EmployeeSalaryTimelineBuilder;
use App\Modules\HR\SalaryIncrease\Contracts\EmployeeSalaryLogServiceInterface;
use App\Modules\HR\SalaryIncrease\Contracts\SalaryIncreaseServiceInterface;
use App\Modules\HR\SalaryIncrease\DTOs\SalaryIncreaseDataDTO;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class EmployeeSalaryLogsFilamentAndEdgeCasesTest extends TestCase
{
    use DatabaseTransactions;

    private function createUserWithRole(int $roleId, ?int $branchId = null): User
    {
        $user = User::create([
            'name' => 'RoleUser '.Str::random(5),
            'email' => 'role_user_'.Str::random(8).'@test.local',
            'password' => bcrypt('secret123'),
            'branch_id' => $branchId,
            'active' => 1,
        ]);

        if (Role::where('id', $roleId)->exists()) {
            DB::table('model_has_roles')->insertOrIgnore([
                'role_id' => $roleId,
                'model_type' => User::class,
                'model_id' => $user->id,
            ]);
        }

        $user->load('roles');

        $panel = Filament::getDefaultPanel();
        if ($panel) {
            Filament::setCurrentPanel($panel);
        }

        return $user;
    }

    private function createBranchAndEmployee(float $salary = 3000.00, string $joinDate = '2025-01-01', bool $seedInitialLog = true): array
    {
        $admin = $this->createUserWithRole(1);
        $branch = Branch::create([
            'name' => 'Probe Branch '.Str::random(5),
            'manager_id' => $admin->id,
            'active' => true,
            'type' => Branch::TYPE_BRANCH,
        ]);
        $admin->updateQuietly(['branch_id' => $branch->id]);

        $empUser = User::create([
            'name' => 'Emp '.Str::random(5),
            'email' => 'emp_'.Str::random(8).'@test.local',
            'password' => bcrypt('secret123'),
            'branch_id' => $branch->id,
            'active' => 1,
        ]);

        $employee = Employee::withoutEvents(fn () => Employee::create([
            'name' => 'Probe Emp '.Str::random(5),
            'email' => $empUser->email,
            'user_id' => $empUser->id,
            'branch_id' => $branch->id,
            'active' => 1,
            'employment_cycle' => 1,
            'join_date' => $joinDate,
            'salary' => $salary,
            'working_hours' => 8,
            'working_days' => 26,
            'no_shift_is_present' => true,
            'has_auto_weekly_leave' => false,
        ]));

        EmployeeBranchLog::create([
            'employee_id' => $employee->id,
            'branch_id' => $branch->id,
            'start_at' => $joinDate.' 00:00:00',
            'end_at' => null,
        ]);

        if ($seedInitialLog && $salary > 0) {
            app(EmployeeSalaryLogServiceInterface::class)->recordInitialSalary(
                $employee,
                $joinDate,
                EmployeeSalaryLog::SOURCE_INITIAL,
                $admin->id
            );
        }

        return [$admin, $branch, $employee];
    }

    private function createPayrollForPeriod(Branch $branch, Employee $employee, int $year, int $month, float $baseSalary): array
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $run = PayrollRun::withoutEvents(fn () => PayrollRun::create([
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
        ]));

        $payroll = Payroll::withoutEvents(fn () => Payroll::create([
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
            'status' => Payroll::STATUS_APPROVED,
            'is_paid' => false,
        ]));

        SalaryTransaction::withoutEvents(fn () => SalaryTransaction::create([
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
        ]));

        return [$run, $payroll];
    }

    public function test_edit_and_view_employee_change_salary_halts_on_error_and_respects_roles(): void
    {
        [$admin, $branch, $employee] = $this->createBranchAndEmployee(3000.00);
        $this->actingAs($admin);

        $currentMonth = now()->copy()->startOfMonth();

        // 1. EditEmployee header action: changeSalary succeeds and closes modal
        Livewire::test(EditEmployee::class, ['record' => $employee->getRouteKey()])
            ->assertActionVisible('changeSalary')
            ->callAction('changeSalary', [
                'new_salary' => 3400,
                'effective_date' => $currentMonth->toDateString(),
                'notes' => 'Livewire changeSalary test',
            ])
            ->assertActionNotMounted('changeSalary')
            ->assertNotified();

        $this->assertEquals(3400.00, (float) $employee->fresh()->salary);

        // 2. ViewEmployee header action: changeSalary succeeds and closes modal
        Livewire::test(ViewEmployee::class, ['record' => $employee->getRouteKey()])
            ->assertActionVisible('changeSalary')
            ->callAction('changeSalary', [
                'new_salary' => 3600,
                'effective_date' => $currentMonth->toDateString(),
                'notes' => 'ViewEmployee changeSalary test',
            ])
            ->assertActionNotMounted('changeSalary')
            ->assertNotified();

        $this->assertEquals(3600.00, (float) $employee->fresh()->salary);

        // 3. Lock current month -> changeSalary MUST halt (keep modal open) and notify
        $this->createPayrollForPeriod($branch, $employee, $currentMonth->year, $currentMonth->month, 3600.00);

        Livewire::test(EditEmployee::class, ['record' => $employee->getRouteKey()])
            ->callAction('changeSalary', [
                'new_salary' => 4000,
                'effective_date' => $currentMonth->toDateString(),
                'notes' => 'Should be blocked by payroll lock',
            ])
            ->assertActionHalted('changeSalary')
            ->assertNotified();

        $this->assertEquals(3600.00, (float) $employee->fresh()->salary);

        // 4. Branch Manager must NOT see changeSalary, Staff must NOT be able to edit Employee
        $branchMgr = $this->createUserWithRole(7, $branch->id);
        $this->actingAs($branchMgr);
        Livewire::test(EditEmployee::class, ['record' => $employee->getRouteKey()])
            ->assertActionHidden('changeSalary');

        $staffUser = $this->createUserWithRole(8, $branch->id);
        $this->actingAs($staffUser);
        $this->assertFalse(EmployeeResource::canEdit($employee));
    }

    public function test_create_and_edit_salary_increase_request_filament_pages_and_resource_auth(): void
    {
        [$admin, $branch, $employee] = $this->createBranchAndEmployee(3000.00);
        $this->actingAs($admin);

        $currentMonth = now()->copy()->startOfMonth();
        $nextMonth = $currentMonth->copy()->addMonth()->startOfMonth();

        // Lock current month with a payroll
        $this->createPayrollForPeriod($branch, $employee, $currentMonth->year, $currentMonth->month, 3000.00);

        // 1. CreateSalaryIncreaseRequest: submitting locked month MUST surface field error on effective_date AND notify
        Livewire::test(CreateSalaryIncreaseRequest::class)
            ->fillForm([
                'employee_id' => $employee->id,
                'effective_date' => $currentMonth->toDateString(),
                'increase_type' => SalaryIncreaseRequest::INCREASE_TYPE_FIXED,
                'increase_amount' => 400,
            ])
            ->call('create')
            ->assertHasFormErrors(['effective_date'])
            ->assertNotified();

        // 2. Creating for unlocked M+1 succeeds
        Livewire::test(CreateSalaryIncreaseRequest::class)
            ->fillForm([
                'employee_id' => $employee->id,
                'effective_date' => $nextMonth->toDateString(),
                'increase_type' => SalaryIncreaseRequest::INCREASE_TYPE_FIXED,
                'increase_amount' => 500,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $req = SalaryIncreaseRequest::where('employee_id', $employee->id)->latest('id')->firstOrFail();
        $this->assertEquals(3500.00, (float) $req->new_salary);
        $this->assertTrue(SalaryIncreaseRequestResource::canEdit($req));
        $this->assertTrue(SalaryIncreaseRequestResource::canDelete($req));
        $this->assertTrue(SalaryIncreaseRequestResource::canForceDelete($req));
        $this->assertTrue(SalaryIncreaseRequestResource::canRestore($req));

        // 3. EditSalaryIncreaseRequest: moving back to locked month surfaces error on effective_date
        Livewire::test(EditSalaryIncreaseRequest::class, ['record' => $req->getRouteKey()])
            ->fillForm([
                'effective_date' => $currentMonth->toDateString(),
                'increase_type' => SalaryIncreaseRequest::INCREASE_TYPE_FIXED,
                'increase_amount' => 500,
            ])
            ->call('save')
            ->assertHasFormErrors(['effective_date'])
            ->assertNotified();

        // 4. Once approved, canEdit/canDelete/canForceDelete/canRestore ALL return false
        $approved = app(SalaryIncreaseServiceInterface::class)->approve($req->id, $admin->id);
        $this->assertFalse(SalaryIncreaseRequestResource::canEdit($approved));
        $this->assertFalse(SalaryIncreaseRequestResource::canDelete($approved));
        $this->assertFalse(SalaryIncreaseRequestResource::canForceDelete($approved));
        $this->assertFalse(SalaryIncreaseRequestResource::canRestore($approved));
    }

    public function test_payrolls_relation_manager_grouped_and_split_rendering_and_sorting(): void
    {
        [$admin, $branch, $employee] = $this->createBranchAndEmployee(3000.00);
        $this->actingAs($admin);

        $currentMonth = now()->copy()->startOfMonth();
        $prevMonth = $currentMonth->copy()->subMonth()->startOfMonth();

        // Raise salary in currentMonth to 3500
        app(EmployeeSalaryLogServiceInterface::class)->recordSalaryChange(
            employee: $employee,
            newSalary: 3500.00,
            effectiveDate: $currentMonth->toDateString(),
            source: EmployeeSalaryLog::SOURCE_DIRECT_EDIT,
            actorId: $admin->id,
        );

        [$runPrev, $payrollPrev] = $this->createPayrollForPeriod($branch, $employee, $prevMonth->year, $prevMonth->month, 3000.00);

        // Test PayrollsRelationManager renders and sorts by base_salary in both grouped and split modes
        Livewire::test(PayrollsRelationManager::class, [
            'ownerRecord' => $runPrev,
            'pageClass' => ViewPayroll::class,
        ])
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$payrollPrev])
            ->sortTable('base_salary', 'asc')
            ->assertSuccessful()
            ->sortTable('base_salary', 'desc')
            ->assertSuccessful()
            ->filterTable('show_branch_splits', true)
            ->assertSuccessful()
            ->sortTable('base_salary', 'asc')
            ->assertSuccessful()
            ->sortTable('base_salary', 'desc')
            ->assertSuccessful();
    }

    public function test_salary_log_observer_blocks_non_latest_delete_and_update_and_resyncs_on_latest_update(): void
    {
        [$admin, $branch, $employee] = $this->createBranchAndEmployee(3000.00);
        $this->actingAs($admin);

        $currentMonth = now()->copy()->startOfMonth();
        $monthPlusOne = $currentMonth->copy()->addMonth()->startOfMonth();
        $monthPlusTwo = $currentMonth->copy()->addMonths(2)->startOfMonth();

        $logService = app(EmployeeSalaryLogServiceInterface::class);

        $logM1 = $logService->recordSalaryChange(
            employee: $employee,
            newSalary: 3500.00,
            effectiveDate: $monthPlusOne->toDateString(),
            source: EmployeeSalaryLog::SOURCE_DIRECT_EDIT,
            actorId: $admin->id,
        );

        $logM2 = $logService->recordSalaryChange(
            employee: $employee,
            newSalary: 4000.00,
            effectiveDate: $monthPlusTwo->toDateString(),
            source: EmployeeSalaryLog::SOURCE_DIRECT_EDIT,
            actorId: $admin->id,
        );

        // 1. Deleting non-latest future log M+1 while M+2 exists MUST be blocked (preserves chain integrity)
        try {
            $logM1->delete();
            $this->fail('Expected ValidationException when deleting a non-latest salary log.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('effective_from', $e->errors());
        }

        // 2. Updating non-latest future log M+1's salary MUST be blocked
        try {
            $logM1->update(['new_salary' => 3600.00]);
            $this->fail('Expected ValidationException when updating a non-latest salary log.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('effective_from', $e->errors());
        }

        // 3. SalaryLogRelationManager hides delete action on M+1 and shows it on M+2
        Livewire::test(SalaryLogRelationManager::class, [
            'ownerRecord' => $employee,
            'pageClass' => EditEmployee::class,
        ])
            ->assertSuccessful()
            ->assertTableActionHidden('delete', $logM1)
            ->assertTableActionVisible('delete', $logM2);

        // 4. Deleting latest log M+2 succeeds, then deleting M+1 succeeds
        $logM2->delete();
        $logM1->delete();
        $this->assertSame(1, EmployeeSalaryLog::where('employee_id', $employee->id)->count());

        // 5. Updating latest unlocked current-month log recalculates change_amount AND re-syncs hr_employees.salary
        $logCurr = $logService->recordSalaryChange(
            employee: $employee,
            newSalary: 3400.00,
            effectiveDate: $currentMonth->toDateString(),
            source: EmployeeSalaryLog::SOURCE_DIRECT_EDIT,
            actorId: $admin->id,
        );
        $this->assertEquals(3400.00, (float) $employee->fresh()->salary);

        $logCurr->update(['new_salary' => 3700.00]);
        $logCurr->refresh();
        $this->assertEquals(700.00, (float) $logCurr->change_amount);
        $this->assertEquals(3700.00, (float) $employee->fresh()->salary);
    }

    public function test_timeline_builder_and_auto_baseline_edge_cases_never_pollute_current_month_payroll(): void
    {
        [$admin, $branch, $employee] = $this->createBranchAndEmployee(
            salary: 3500.00,
            joinDate: '2026-01-10',
            seedInitialLog: false
        );

        $currentMonth = Carbon::create(2026, 11, 1)->startOfMonth();

        // 1. Unsorted observations INCLUDING current month (2026-11) at 3000 while hr_employees.salary = 3500:
        //    Layer 3 MUST reconcile at 2026-12-01 (nextAfterLast), NOT 2026-11-01!
        $rows = EmployeeSalaryTimelineBuilder::forEmployee($employee)
            ->withPayrollObservations([
                '2026-11-01' => 3000.00,
                '2026-09-01' => 3000.00,
                '2026-10-01' => 3000.00,
            ])
            ->reconciledAsOf($currentMonth)
            ->build();

        $this->assertCount(2, $rows);
        $this->assertSame('2026-01-01', $rows[0]['effective_from']);
        $this->assertEquals(3000.00, (float) $rows[0]['new_salary']);
        $this->assertSame('2026-12-01', $rows[1]['effective_from']);
        $this->assertEquals(3500.00, (float) $rows[1]['new_salary']);

        // 2. Rehired employee whose join_date (2026-06-15) is later than baselineMonth (2026-04-01)
        [$admin2, $branch2, $rehiredEmp] = $this->createBranchAndEmployee(
            salary: 3000.00,
            joinDate: '2026-06-15',
            seedInitialLog: false
        );
        $this->actingAs($admin2);

        app(EmployeeSalaryLogServiceInterface::class)->recordSalaryChange(
            employee: $rehiredEmp,
            newSalary: 3300.00,
            effectiveDate: '2026-05-10',
            source: EmployeeSalaryLog::SOURCE_DIRECT_EDIT,
            actorId: $admin2->id,
        );

        $baseline = EmployeeSalaryLog::where('employee_id', $rehiredEmp->id)
            ->where('source', EmployeeSalaryLog::SOURCE_AUTO_BASELINE)
            ->firstOrFail();

        $this->assertSame('2026-04-01', $baseline->effective_from->toDateString());
        $this->assertSame('2026-04-01', $baseline->effective_date->toDateString());
    }

    public function test_pending_checker_aggregated_branch_breakdown_without_branch_filter(): void
    {
        [$admin, $branch, $employee] = $this->createBranchAndEmployee(3000.00);
        $this->actingAs($admin);

        $currentMonth = now()->copy()->startOfMonth();

        app(SalaryIncreaseServiceInterface::class)->create(SalaryIncreaseDataDTO::fromArray([
            'employee_id' => $employee->id,
            'increase_type' => SalaryIncreaseRequest::INCREASE_TYPE_FIXED,
            'increase_amount' => 300,
            'effective_date' => $currentMonth->toDateString(),
        ], $admin->id));

        $summary = app(MonthlyPendingApplicationChecker::class)->getDashboardSummary([
            'year' => $currentMonth->year,
            'month' => $currentMonth->month,
        ]);

        $this->assertTrue($summary['has_pending']);
        $this->assertArrayHasKey('by_branch', $summary);
        $branchEntry = collect($summary['by_branch'])->firstWhere('branch_id', $branch->id);
        $this->assertNotNull($branchEntry);
        $this->assertGreaterThanOrEqual(1, $branchEntry['count']);
    }
}
