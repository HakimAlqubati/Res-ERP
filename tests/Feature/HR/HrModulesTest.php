<?php

declare(strict_types=1);

namespace Tests\Feature\HR;

use App\Http\Middleware\CheckUserActive;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeApplicationV2;
use App\Models\EmployeeBranchLog;
use App\Models\EmployeeServiceTermination;
use App\Models\SalaryIncreaseRequest;
use App\Models\User;
use App\Modules\HR\Attendance\Enums\AttendanceType;
use App\Modules\HR\Attendance\Services\Validator\Rules\HistoricalBranchEligibilityRule;
use App\Modules\HR\Employee\Services\AuthEligibilityService;
use App\Modules\HR\Employee\Services\EmployeeLifecycleService;
use App\Modules\HR\EmployeeApplications\Services\ApplicationApproverResolver;
use App\Modules\HR\SalaryIncrease\Builders\SalaryIncreasePayloadBuilder;
use App\Modules\HR\SalaryIncrease\Contracts\SalaryIncreaseServiceInterface;
use App\Modules\HR\SalaryIncrease\DTOs\SalaryIncreaseDataDTO;
use App\Services\HR\Applications\EmployeeApplicationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class HrModulesTest extends TestCase
{
    use DatabaseTransactions;

    private function createBranchAndManager(string $suffix): array
    {
        $managerUser = User::create([
            'name'     => "Manager {$suffix}",
            'email'    => "manager_{$suffix}_" . Str::random(6) . '@test.local',
            'password' => bcrypt('secret123'),
            'active'   => 1,
        ]);

        $branch = Branch::create([
            'name'       => "Branch {$suffix} " . Str::random(4),
            'manager_id' => $managerUser->id,
            'active'     => true,
            'type'       => Branch::TYPE_BRANCH,
        ]);

        $managerUser->updateQuietly(['branch_id' => $branch->id]);

        $managerEmployee = Employee::withoutEvents(function () use ($managerUser, $branch, $suffix) {
            return Employee::create([
                'name'        => "Manager Emp {$suffix} " . Str::random(4),
                'email'       => $managerUser->email,
                'user_id'     => $managerUser->id,
                'branch_id'   => $branch->id,
                'active'      => 1,
                'join_date'   => '2025-01-01',
                'salary'      => 5000,
                'working_hours' => 8,
                'working_days'  => 26,
            ]);
        });

        return [$branch, $managerUser, $managerEmployee];
    }

    private function createTestEmployee(Branch $branch, ?Employee $manager = null, float $salary = 3000): array
    {
        $user = User::create([
            'name'      => 'Emp User ' . Str::random(5),
            'email'     => 'emp_' . Str::random(8) . '@test.local',
            'password'  => bcrypt('secret123'),
            'branch_id' => $branch->id,
            'active'    => 1,
        ]);

        $employee = Employee::withoutEvents(function () use ($user, $branch, $manager, $salary) {
            return Employee::create([
                'name'             => 'Test Employee ' . Str::random(6),
                'email'            => $user->email,
                'user_id'          => $user->id,
                'branch_id'        => $branch->id,
                'manager_id'       => $manager?->id,
                'active'           => 1,
                'employment_cycle' => 1,
                'join_date'        => '2025-01-01',
                'salary'           => $salary,
                'working_hours'    => 8,
                'working_days'     => 26,
            ]);
        });

        return [$employee, $user];
    }

    public function test_task1_termination_request_revokes_passport_tokens_and_blocks_session(): void
    {
        [$branch, $managerUser, $managerEmployee] = $this->createBranchAndManager('T1');
        [$employee, $user] = $this->createTestEmployee($branch, $managerEmployee);

        $tokenId = Str::random(40);
        if (Schema::hasTable('oauth_access_tokens')) {
            DB::table('oauth_access_tokens')->insert([
                'id'         => $tokenId,
                'user_id'    => $user->id,
                'client_id'  => 1,
                'name'       => 'MyApp',
                'scopes'     => '[]',
                'revoked'    => false,
                'created_at' => now(),
                'updated_at' => now(),
                'expires_at' => now()->addDays(30),
            ]);
        }

        if (Schema::hasTable('oauth_refresh_tokens')) {
            DB::table('oauth_refresh_tokens')->insert([
                'id'              => Str::random(40),
                'access_token_id' => $tokenId,
                'revoked'         => false,
                'expires_at'      => now()->addDays(60),
            ]);
        }

        $oldRemember = $user->remember_token;

        $this->actingAs($managerUser);
        app(EmployeeLifecycleService::class)->requestTermination($employee, [
            'termination_date'   => now()->toDateString(),
            'termination_reason' => 'Policy test',
        ]);

        if (Schema::hasTable('oauth_access_tokens')) {
            $this->assertTrue((bool) DB::table('oauth_access_tokens')->where('id', $tokenId)->value('revoked'));
        }
        if (Schema::hasTable('oauth_refresh_tokens')) {
            $this->assertTrue((bool) DB::table('oauth_refresh_tokens')->where('access_token_id', $tokenId)->value('revoked'));
        }

        $this->assertNotSame($oldRemember, $user->fresh()->remember_token);
        $this->assertFalse(app(AuthEligibilityService::class)->canAuthenticate($user->fresh()));

        $middleware = app(CheckUserActive::class);
        $request = Request::create('/api/user', 'GET');
        $request->headers->set('Accept', 'application/json');
        $request->setUserResolver(fn () => $user->fresh());

        $response = $middleware->handle($request, fn () => response()->json(['ok' => true]));
        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_task2_missed_attendance_routes_to_historical_branch_and_notifies_old_manager(): void
    {
        [$branchA, $managerUserA, $managerEmpA] = $this->createBranchAndManager('A');
        [$branchB, $managerUserB, $managerEmpB] = $this->createBranchAndManager('B');

        // Employee currently in Branch B (with Manager B), previously in Branch A
        [$employee, $empUser] = $this->createTestEmployee($branchB, $managerEmpB);

        EmployeeBranchLog::create([
            'employee_id' => $employee->id,
            'branch_id'   => $branchA->id,
            'start_at'    => '2026-10-01 00:00:00',
            'end_at'      => '2026-11-10 00:00:00',
            'created_by'  => $managerUserA->id,
        ]);

        EmployeeBranchLog::create([
            'employee_id' => $employee->id,
            'branch_id'   => $branchB->id,
            'start_at'    => '2026-11-10 00:00:00',
            'end_at'      => null,
            'created_by'  => $managerUserB->id,
        ]);

        $this->actingAs($empUser);
        $appService = app(EmployeeApplicationService::class);

        $missedCheckinApp = $appService->createApplication([
            'employee_id'         => $employee->id,
            'application_type_id' => EmployeeApplicationV2::APPLICATION_TYPE_ATTENDANCE_FINGERPRINT_REQUEST,
            'application_date'    => '2026-11-15',
            'notes'               => 'Forgot check-in in old branch',
            'missed_checkin_request' => [
                'date' => '2026-11-05',
                'time' => '08:30',
            ],
        ]);

        $this->assertSame($branchA->id, (int) $missedCheckinApp->branch_id);

        $recipient = app(ApplicationApproverResolver::class)->resolve($missedCheckinApp);
        $this->assertNotNull($recipient);
        $this->assertSame($managerUserA->id, $recipient->id);

        // Regular leave request still goes to current Branch B
        $leaveApp = $appService->createApplication([
            'employee_id'         => $employee->id,
            'application_type_id' => EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST,
            'application_date'    => '2026-11-15',
            'notes'               => 'Regular leave',
        ]);
        $this->assertSame($branchB->id, (int) $leaveApp->branch_id);

        // Historical branch rule permits AttendanceType::REQUEST for old branch on old date,
        // while still rejecting live RFID check-in at old branch
        $eligibilityRule = app(HistoricalBranchEligibilityRule::class);
        $this->assertTrue($eligibilityRule->isAllowed(
            $employee,
            $branchA->id,
            \Carbon\Carbon::parse('2026-11-05 08:30:00'),
            AttendanceType::REQUEST->value
        ));
        $this->assertFalse($eligibilityRule->isAllowed(
            $employee,
            $branchA->id,
            \Carbon\Carbon::parse('2026-11-05 08:30:00'),
            AttendanceType::RFID->value
        ));
    }

    public function test_task3_salary_increase_workflow_updates_employee_salary_and_tracks_old_salary(): void
    {
        [$branch, $managerUser, $managerEmp] = $this->createBranchAndManager('S3');
        [$employee] = $this->createTestEmployee($branch, $managerEmp, salary: 3000.00);

        $this->actingAs($managerUser);
        $service = app(SalaryIncreaseServiceInterface::class);

        // Test Builder calculation variants
        $pctAmounts = SalaryIncreasePayloadBuilder::calculateAmounts(3000.00, SalaryIncreaseRequest::INCREASE_TYPE_PERCENTAGE, 10);
        $this->assertSame(300.00, $pctAmounts['increase_amount']);
        $this->assertSame(3300.00, $pctAmounts['new_salary']);

        // Create fixed increase request
        $request = $service->create(SalaryIncreaseDataDTO::fromArray([
            'employee_id'     => $employee->id,
            'increase_type'   => SalaryIncreaseRequest::INCREASE_TYPE_FIXED,
            'increase_amount' => 500,
            'effective_date'  => now()->toDateString(),
            'notes'           => 'Annual performance increment',
        ], $managerUser->id));

        $this->assertSame(SalaryIncreaseRequest::STATUS_PENDING, $request->status);
        $this->assertEquals(3000.00, (float) $request->old_salary);
        $this->assertEquals(3500.00, (float) $request->new_salary);

        // Approve -> updates hr_employees.salary and retains old_salary
        $approved = $service->approve($request->id, $managerUser->id);
        $this->assertSame(SalaryIncreaseRequest::STATUS_APPROVED, $approved->status);
        $this->assertEquals(3000.00, (float) $approved->old_salary);
        $this->assertEquals(3500.00, (float) $employee->fresh()->salary);

        // Double approval must fail cleanly
        $this->expectException(ValidationException::class);
        $service->approve($request->id, $managerUser->id);
    }

    public function test_task5_rehire_allows_creating_second_termination_and_targets_latest_request(): void
    {
        [$branch, $managerUser, $managerEmp] = $this->createBranchAndManager('R5');
        [$employee] = $this->createTestEmployee($branch, $managerEmp);

        $this->actingAs($managerUser);
        $lifecycle = app(EmployeeLifecycleService::class);

        // 1. First termination + approval
        $first = $lifecycle->requestTermination($employee, [
            'termination_date'   => now()->toDateString(),
            'termination_reason' => 'Resigned cycle 1',
        ]);
        $lifecycle->approveTermination($first, $managerUser->id);

        $this->assertEquals(0, $employee->fresh()->active);
        $this->assertSame(EmployeeServiceTermination::STATUS_APPROVED, $first->fresh()->status);

        // 2. Rehire employee -> advances employment_cycle and preserves old approved record
        $lifecycle->rehire($employee->fresh(), [
            'join_date' => now()->toDateString(),
            'notes'     => 'Rehired for cycle 2',
        ]);

        $rehiredEmployee = $employee->fresh();
        $this->assertEquals(1, $rehiredEmployee->active);
        $this->assertSame(2, $rehiredEmployee->employment_cycle);
        $this->assertSame(EmployeeServiceTermination::STATUS_APPROVED, $first->fresh()->status);

        // 3. Second termination request must succeed cleanly
        $second = $lifecycle->requestTermination($rehiredEmployee, [
            'termination_date'   => now()->toDateString(),
            'termination_reason' => 'Resigned cycle 2',
        ]);

        $this->assertSame(2, $second->employment_cycle);
        $this->assertSame(EmployeeServiceTermination::STATUS_PENDING, $second->status);
        $this->assertSame($second->id, $rehiredEmployee->fresh()->pendingTerminationRequest->id);
        $this->assertSame($second->id, $rehiredEmployee->fresh()->serviceTermination->id);

        // 4. Attempting a third request while second is still pending must still be rejected
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Employee already has an active termination request.');

        $lifecycle->requestTermination($rehiredEmployee->fresh(), [
            'termination_date'   => now()->toDateString(),
            'termination_reason' => 'Duplicate pending request',
        ]);
    }

    public function test_http_endpoints_for_salary_increase_work_end_to_end(): void
    {
        [$branch, $managerUser, $managerEmp] = $this->createBranchAndManager('API');
        [$employee] = $this->createTestEmployee($branch, $managerEmp, salary: 4000.00);

        $this->actingAs($managerUser, 'api');

        $createRes = $this->postJson('/api/hr/salary-increases', [
            'employee_id'         => $employee->id,
            'increase_type'       => SalaryIncreaseRequest::INCREASE_TYPE_PERCENTAGE,
            'increase_percentage' => 25,
            'notes'               => 'Promotion increment',
        ]);

        $createRes->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.old_salary', 4000)
            ->assertJsonPath('data.new_salary', 5000);

        $requestId = $createRes->json('data.id');

        $approveRes = $this->postJson("/api/hr/salary-increases/{$requestId}/approve");
        $approveRes->assertStatus(200)
            ->assertJsonPath('data.status', SalaryIncreaseRequest::STATUS_APPROVED);

        $this->assertEquals(5000.00, (float) $employee->fresh()->salary);
    }
}
