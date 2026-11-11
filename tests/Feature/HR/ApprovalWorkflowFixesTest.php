<?php

namespace Tests\Feature\HR;

use App\Filament\Clusters\HRApplicationsCluster\Resources\EmployeeApplicationResource;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeApplicationV2;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Spatie\Permission\Models\Role;
use App\Modules\HR\ApprovalPolicies\Enums\ApprovalMode;
use App\Modules\HR\ApprovalPolicies\Enums\ApprovalPolicyStepType;
use App\Modules\HR\ApprovalPolicies\Enums\ApprovalStepStatus;
use App\Modules\HR\ApprovalPolicies\Models\ApprovalPolicy;
use App\Modules\HR\ApprovalPolicies\Models\ApprovalStep;
use App\Modules\HR\ApprovalPolicies\Rules\PolicyNotInUse;
use App\Modules\HR\ApprovalPolicies\Rules\UniqueApprovalPolicy;
use App\Modules\HR\ApprovalPolicies\Services\ApprovalPolicyResolver;
use App\Modules\HR\ApprovalPolicies\Services\ApprovalWorkflowRequirementChecker;
use App\Modules\HR\ApprovalPolicies\Services\ApprovalWorkflowService;
use App\Modules\HR\ApprovalPolicies\Support\ApprovalSubjectRegistry;
use App\Services\HR\Applications\EmployeeApplicationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ApprovalWorkflowFixesTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        ApprovalWorkflowRequirementChecker::clearCache();
        ApprovalWorkflowService::clearCache();

        // Isolate test policies by deactivating pre-existing policies for EmployeeApplicationV2 inside the transaction
        ApprovalPolicy::query()
            ->where('approvable_type', EmployeeApplicationV2::class)
            ->update(['active' => false]);
    }

    private function createTestBranch(string $name = 'Test Branch', ?int $managerUserId = null): Branch
    {
        return Branch::withoutEvents(fn () => Branch::query()->create([
            'name' => $name,
            'active' => true,
            'manager_id' => $managerUserId,
        ]));
    }

    private function createTestUserAndEmployee(
        string $name,
        int $branchId,
        ?int $managerEmployeeId = null
    ): array {
        $unique = uniqid();

        $user = User::withoutEvents(fn () => User::query()->create([
            'name' => $name,
            'email' => "test_{$unique}@example.com",
            'password' => bcrypt('password'),
            'branch_id' => $branchId,
            'active' => 1,
        ]));

        $employee = Employee::withoutEvents(fn () => Employee::query()->create([
            'name' => $name,
            'email' => "emp_{$unique}@example.com",
            'user_id' => $user->id,
            'branch_id' => $branchId,
            'manager_id' => $managerEmployeeId,
            'employee_no' => "EMP-{$unique}",
            'active' => 1,
            'salary' => 5000,
        ]));

        return [$user, $employee];
    }

    private function createLeaveApplication(Employee $employee, User $creator): EmployeeApplicationV2
    {
        $this->actingAs($creator);

        $leaveType = LeaveType::withoutEvents(fn () => LeaveType::query()->create([
            'name' => 'Annual Leave ' . uniqid(),
            'count_days' => 30,
            'active' => true,
            'type' => LeaveType::TYPE_YEARLY,
            'balance_period' => LeaveType::BALANCE_PERIOD_YEARLY,
            'is_paid' => true,
            'created_by' => $creator->id,
        ]));

        LeaveBalance::withoutEvents(fn () => LeaveBalance::query()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'year' => 2099,
            'month' => 1,
            'branch_id' => $employee->branch_id,
            'entitled_days' => 30,
            'supposed_days' => 30,
            'used_days' => 0,
            'pending_days' => 1,
            'created_by' => $creator->id,
        ]));

        $app = EmployeeApplicationV2::query()->create([
            'employee_id' => $employee->id,
            'branch_id' => $employee->branch_id,
            'application_date' => '2099-01-15',
            'status' => EmployeeApplicationV2::STATUS_PENDING,
            'application_type_id' => EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST,
            'application_type_name' => EmployeeApplicationV2::APPLICATION_TYPE_NAMES[EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST],
            'created_by' => $creator->id,
            'notes' => 'Test leave application',
        ]);

        LeaveRequest::withoutEvents(fn () => LeaveRequest::query()->create([
            'application_id' => $app->id,
            'employee_id' => $employee->id,
            'application_type_id' => EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST,
            'application_type_name' => EmployeeApplicationV2::APPLICATION_TYPE_NAMES[EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST],
            'leave_type' => $leaveType->id,
            'start_date' => '2099-01-15',
            'end_date' => '2099-01-15',
            'days_count' => 1,
            'year' => 2099,
            'month' => 1,
            'reason' => 'Test leave application',
        ]));

        return $app;
    }

    /**
     * Observation #1: Ability to edit or disable policies after they have been used.
     */
    public function test_observation_1_allows_editing_and_disabling_used_policy(): void
    {
        $branch = $this->createTestBranch('Branch Obs 1');
        [$managerUser, $managerEmp] = $this->createTestUserAndEmployee('Manager Obs 1', $branch->id);
        [$empUser, $employee] = $this->createTestUserAndEmployee('Employee Obs 1', $branch->id, $managerEmp->id);

        $policy = ApprovalPolicy::query()->create([
            'name' => 'Original Policy Name',
            'approvable_type' => EmployeeApplicationV2::class,
            'application_type_id' => EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST,
            'branch_ids' => [$branch->id],
            'mode' => ApprovalMode::DIRECT_MANAGER,
            'active' => true,
            'priority' => 1,
        ]);

        $app = $this->createLeaveApplication($employee, $empUser);
        $this->assertDatabaseHas('hr_approval_steps', [
            'approvable_type' => EmployeeApplicationV2::class,
            'approvable_id' => $app->id,
            'approval_policy_id' => $policy->id,
            'status' => ApprovalStepStatus::PENDING,
        ]);

        // Editing name and disabling active flag on a used policy must succeed without throwing
        $policy->update([
            'name' => 'Updated Policy Name',
            'active' => false,
        ]);

        $this->assertSame('Updated Policy Name', $policy->fresh()->name);
        $this->assertFalse($policy->fresh()->active);

        // PolicyNotInUse rule should fail while there is a PENDING step, and pass once the step is no longer PENDING
        $validatorPending = Validator::make(
            ['policy' => $policy->id],
            ['policy' => [new PolicyNotInUse($policy)]]
        );
        $this->assertTrue($validatorPending->fails());

        $app->approvalSteps()->update(['status' => ApprovalStepStatus::APPROVED]);

        $validatorCompleted = Validator::make(
            ['policy' => $policy->id],
            ['policy' => [new PolicyNotInUse($policy)]]
        );
        $this->assertFalse($validatorCompleted->fails());
    }

    /**
     * Observation #2: Preventing duplicate policies for the same request type and overlapping branches.
     */
    public function test_observation_2_blocks_duplicate_policies_for_same_request_and_branch(): void
    {
        $branchA = $this->createTestBranch('Branch A');
        $branchB = $this->createTestBranch('Branch B');
        $branchC = $this->createTestBranch('Branch C');

        ApprovalPolicy::query()->create([
            'name' => 'Leave Policy One',
            'approvable_type' => EmployeeApplicationV2::class,
            'application_type_id' => EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST,
            'branch_ids' => [$branchA->id, $branchB->id],
            'mode' => ApprovalMode::DIRECT_MANAGER,
            'active' => true,
            'priority' => 1,
        ]);

        // Different name, same request type, overlapping Branch B -> MUST FAIL
        $overlappingRule = new UniqueApprovalPolicy(
            ignoreId: null,
            approvableType: EmployeeApplicationV2::class,
            applicationTypeId: EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST,
            branchIds: [$branchB->id, $branchC->id]
        );
        $validatorOverlap = Validator::make(['name' => 'Completely Different Name'], ['name' => [$overlappingRule]]);
        $this->assertTrue($validatorOverlap->fails());

        // Same request type, non-overlapping Branch C -> MUST PASS
        $nonOverlappingRule = new UniqueApprovalPolicy(
            ignoreId: null,
            approvableType: EmployeeApplicationV2::class,
            applicationTypeId: EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST,
            branchIds: [$branchC->id]
        );
        $validatorNonOverlap = Validator::make(['name' => 'Branch C Policy'], ['name' => [$nonOverlappingRule]]);
        $this->assertFalse($validatorNonOverlap->fails());
    }

    /**
     * Observation #3: Skipping the Direct Manager step when the employee has no manager.
     */
    public function test_observation_3_skips_direct_manager_step_when_employee_has_no_manager(): void
    {
        $branch = $this->createTestBranch('Branch Obs 3');
        [$hrUser, $hrEmp] = $this->createTestUserAndEmployee('HR Approver', $branch->id);
        [$empUser, $employeeWithoutManager] = $this->createTestUserAndEmployee('No Manager Emp', $branch->id, null);

        $policy = ApprovalPolicy::query()->create([
            'name' => 'Two Step Policy',
            'approvable_type' => EmployeeApplicationV2::class,
            'application_type_id' => EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST,
            'branch_ids' => [$branch->id],
            'mode' => ApprovalMode::CONFIGURED_STEPS,
            'active' => true,
            'priority' => 1,
        ]);

        $policy->policySteps()->createMany([
            [
                'step_order' => 1,
                'approver_type' => ApprovalPolicyStepType::DIRECT_MANAGER,
            ],
            [
                'step_order' => 2,
                'approver_type' => ApprovalPolicyStepType::CUSTOM_USER,
                'approver_user_id' => $hrUser->id,
            ],
        ]);

        $app = $this->createLeaveApplication($employeeWithoutManager, $empUser);
        $steps = $app->approvalSteps()->orderBy('step_order')->get();

        $this->assertCount(2, $steps);
        $this->assertSame(ApprovalStepStatus::SKIPPED, $steps[0]->status);
        $this->assertSame(ApprovalStepStatus::PENDING, $steps[1]->status);

        // Current active step is immediately Step 2 (HR User)
        $this->assertSame(2, $app->currentApprovalStep->step_order);

        // HR User approves Step 2 and finalizes the application
        app(EmployeeApplicationService::class)->approveApplication($app->id, $hrUser->id);

        $this->assertSame(EmployeeApplicationV2::STATUS_APPROVED, $app->fresh()->status);
        $this->assertSame(ApprovalStepStatus::APPROVED, $steps[1]->fresh()->status);
    }

    /**
     * Observation #4: Preventing a manager without approval permission on the current step from rejecting.
     */
    public function test_observation_4_prevents_unauthorized_manager_from_rejecting_request(): void
    {
        $branch = $this->createTestBranch('Branch Obs 4');
        [$directManagerUser, $directManagerEmp] = $this->createTestUserAndEmployee('Direct Manager', $branch->id);
        [$otherManagerUser, $otherManagerEmp] = $this->createTestUserAndEmployee('Other Manager', $branch->id);
        [$empUser, $employee] = $this->createTestUserAndEmployee('Employee Obs 4', $branch->id, $directManagerEmp->id);

        ApprovalPolicy::query()->create([
            'name' => 'Direct Manager Policy',
            'approvable_type' => EmployeeApplicationV2::class,
            'application_type_id' => EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST,
            'branch_ids' => [$branch->id],
            'mode' => ApprovalMode::DIRECT_MANAGER,
            'active' => true,
            'priority' => 1,
        ]);

        $app = $this->createLeaveApplication($employee, $empUser);

        $this->expectException(AuthorizationException::class);
        app(EmployeeApplicationService::class)->rejectApplication($app->id, $otherManagerUser->id, 'Unauthorized rejection');
    }

    /**
     * Observation #5: Resetting approval steps to pending when undoing approval (Rollback).
     */
    public function test_observation_5_resets_approval_steps_on_rollback_and_re_enters_workflow(): void
    {
        $branch = $this->createTestBranch('Branch Obs 5');
        [$managerUser, $managerEmp] = $this->createTestUserAndEmployee('Direct Manager Obs 5', $branch->id);
        [$hrUser, $hrEmp] = $this->createTestUserAndEmployee('HR Role User Obs 5', $branch->id);
        [$empUser, $employee] = $this->createTestUserAndEmployee('Employee Obs 5', $branch->id, $managerEmp->id);

        $role = Role::query()->create([
            'name' => 'HR_Approver_Role_' . uniqid(),
            'guard_name' => 'web',
        ]);
        $hrUser->assignRole($role);
        ApprovalWorkflowService::clearCache();

        $policy = ApprovalPolicy::query()->create([
            'name' => 'Rollback Policy',
            'approvable_type' => EmployeeApplicationV2::class,
            'application_type_id' => EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST,
            'branch_ids' => [$branch->id],
            'mode' => ApprovalMode::CONFIGURED_STEPS,
            'active' => true,
            'priority' => 1,
        ]);

        $policy->policySteps()->createMany([
            [
                'step_order' => 1,
                'approver_type' => ApprovalPolicyStepType::DIRECT_MANAGER,
            ],
            [
                'step_order' => 2,
                'approver_type' => ApprovalPolicyStepType::CUSTOM_ROLE,
                'approver_role_id' => $role->id,
            ],
        ]);

        $app = $this->createLeaveApplication($employee, $empUser);
        $service = app(EmployeeApplicationService::class);

        // Approve Step 1 (Direct Manager) & Step 2 (HR Role)
        $service->approveApplication($app->id, $managerUser->id);
        $this->assertSame(EmployeeApplicationV2::STATUS_PENDING, $app->fresh()->status);

        $service->approveApplication($app->id, $hrUser->id);
        $this->assertSame(EmployeeApplicationV2::STATUS_APPROVED, $app->fresh()->status);

        // Undo Approval (Rollback)
        $service->undoApproveApplication($app->id, $hrUser->id);

        $app->refresh();
        $this->assertSame(EmployeeApplicationV2::STATUS_PENDING, $app->status);
        $this->assertNull($app->approved_by);

        $steps = $app->approvalSteps()->orderBy('step_order')->get();
        $this->assertCount(2, $steps);

        // Step 1 (user-based) is back to pending and retains its assigned manager
        $this->assertSame(ApprovalStepStatus::PENDING, $steps[0]->status);
        $this->assertSame($managerUser->id, $steps[0]->approver_user_id);
        $this->assertNull($steps[0]->approved_at);

        // Step 2 (role-based) is back to pending and cleared previous acting user/employee
        $this->assertSame(ApprovalStepStatus::PENDING, $steps[1]->status);
        $this->assertSame($role->id, $steps[1]->approver_role_id);
        $this->assertNull($steps[1]->approver_user_id);
        $this->assertNull($steps[1]->approver_employee_id);
        $this->assertNull($steps[1]->approved_at);

        // Verify workflow is required again starting from Step 1
        $this->assertTrue(app(ApprovalWorkflowRequirementChecker::class)->requiresWorkflow($app));
        $this->assertSame(1, $app->currentApprovalStep->step_order);
    }

    /**
     * Observation #6: Preventing an employee (such as a Branch Manager) from approving their own personal request.
     */
    public function test_observation_6_prevents_self_approval_even_when_assigned_to_user_role(): void
    {
        $branch = $this->createTestBranch('Branch Obs 6');
        [$branchManagerUser, $branchManagerEmp] = $this->createTestUserAndEmployee('Branch Manager Self', $branch->id);
        [$otherRoleUser, $otherRoleEmp] = $this->createTestUserAndEmployee('Other Branch Manager', $branch->id);

        $role = Role::query()->create([
            'name' => 'Branch_Manager_Role_' . uniqid(),
            'guard_name' => 'web',
        ]);
        $branchManagerUser->assignRole($role);
        $otherRoleUser->assignRole($role);
        ApprovalWorkflowService::clearCache();

        $policy = ApprovalPolicy::query()->create([
            'name' => 'Role Based Policy',
            'approvable_type' => EmployeeApplicationV2::class,
            'application_type_id' => EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST,
            'branch_ids' => [$branch->id],
            'mode' => ApprovalMode::CONFIGURED_STEPS,
            'active' => true,
            'priority' => 1,
        ]);

        $policy->policySteps()->create([
            'step_order' => 1,
            'approver_type' => ApprovalPolicyStepType::CUSTOM_ROLE,
            'approver_role_id' => $role->id,
        ]);

        // Branch Manager submits their own Leave Request
        $app = $this->createLeaveApplication($branchManagerEmp, $branchManagerUser);

        $workflowService = app(ApprovalWorkflowService::class);
        $this->assertTrue($workflowService->isSelfApproval($app, $branchManagerUser));
        $this->assertFalse($workflowService->canUserApprove($app, $branchManagerUser));

        $this->actingAs($branchManagerUser);
        $this->assertFalse(EmployeeApplicationResource::canCurrentUserActOnApplication(
            $app,
            EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST
        ));

        // Attempting to self-approve via service must throw AuthorizationException
        try {
            app(EmployeeApplicationService::class)->approveApplication($app->id, $branchManagerUser->id);
            $this->fail('Expected AuthorizationException for self-approval was not thrown.');
        } catch (AuthorizationException $e) {
            $this->assertStringContainsString('cannot approve or reject your own request', $e->getMessage());
        }

        // Another user with the same role CAN approve it
        app(EmployeeApplicationService::class)->approveApplication($app->id, $otherRoleUser->id);
        $this->assertSame(EmployeeApplicationV2::STATUS_APPROVED, $app->fresh()->status);
    }

    /**
     * Observation #7: Applying a policy to all branches when no branch is selected (empty array [] or null).
     */
    public function test_observation_7_applies_policy_to_all_branches_when_branch_ids_is_empty_or_null(): void
    {
        $branch1 = $this->createTestBranch('Global Branch 1');
        $branch2 = $this->createTestBranch('Global Branch 2');
        [$managerUser, $managerEmp] = $this->createTestUserAndEmployee('Global Manager', $branch1->id);
        [$emp1User, $emp1] = $this->createTestUserAndEmployee('Emp Branch 1', $branch1->id, $managerEmp->id);
        [$emp2User, $emp2] = $this->createTestUserAndEmployee('Emp Branch 2', $branch2->id, $managerEmp->id);

        // Passing [] (as Filament multiple Select does when nothing is selected)
        $policy = ApprovalPolicy::query()->create([
            'name' => 'Global Policy For All Branches',
            'approvable_type' => EmployeeApplicationV2::class,
            'application_type_id' => EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST,
            'branch_ids' => [],
            'mode' => ApprovalMode::DIRECT_MANAGER,
            'active' => true,
            'priority' => 1,
        ]);

        // Mutator normalizes [] to null
        $this->assertNull($policy->fresh()->branch_ids);

        $app1 = $this->createLeaveApplication($emp1, $emp1User);
        $app2 = $this->createLeaveApplication($emp2, $emp2User);

        $this->assertSame($policy->id, app(ApprovalPolicyResolver::class)->resolve($app1)->id);
        $this->assertSame($policy->id, app(ApprovalPolicyResolver::class)->resolve($app2)->id);

        // Even if raw '[]' JSON exists in DB from legacy records, matchingRecord still resolves it
        DB::table('hr_approval_policies')
            ->where('id', $policy->id)
            ->update(['branch_ids' => json_encode([])]);
        ApprovalWorkflowRequirementChecker::clearCache();

        $this->assertTrue(app(ApprovalWorkflowRequirementChecker::class)->hasActivePolicy($app1));
        $this->assertSame($policy->id, app(ApprovalPolicyResolver::class)->resolve($app2)->id);
    }

    /**
     * Observation #8: Updating approval steps status to rejected (and subsequent steps to skipped) on rejection.
     */
    public function test_observation_8_reflects_rejection_in_approval_steps(): void
    {
        $branch = $this->createTestBranch('Branch Obs 8');
        [$managerUser, $managerEmp] = $this->createTestUserAndEmployee('Manager Obs 8', $branch->id);
        [$secondApproverUser, $secondApproverEmp] = $this->createTestUserAndEmployee('Second Approver Obs 8', $branch->id);
        [$empUser, $employee] = $this->createTestUserAndEmployee('Employee Obs 8', $branch->id, $managerEmp->id);

        $policy = ApprovalPolicy::query()->create([
            'name' => 'Rejection Steps Policy',
            'approvable_type' => EmployeeApplicationV2::class,
            'application_type_id' => EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST,
            'branch_ids' => [$branch->id],
            'mode' => ApprovalMode::CONFIGURED_STEPS,
            'active' => true,
            'priority' => 1,
        ]);

        $policy->policySteps()->createMany([
            [
                'step_order' => 1,
                'approver_type' => ApprovalPolicyStepType::DIRECT_MANAGER,
            ],
            [
                'step_order' => 2,
                'approver_type' => ApprovalPolicyStepType::CUSTOM_USER,
                'approver_user_id' => $secondApproverUser->id,
            ],
        ]);

        $app = $this->createLeaveApplication($employee, $empUser);

        app(EmployeeApplicationService::class)->rejectApplication($app->id, $managerUser->id, 'Insufficient workload coverage');

        $app->refresh();
        $this->assertSame(EmployeeApplicationV2::STATUS_REJECTED, $app->status);
        $this->assertSame('Insufficient workload coverage', $app->rejected_reason);

        $steps = $app->approvalSteps()->orderBy('step_order')->get();
        $this->assertCount(2, $steps);

        // Step 1 is marked REJECTED with notes and timestamp
        $this->assertSame(ApprovalStepStatus::REJECTED, $steps[0]->status);
        $this->assertNotNull($steps[0]->rejected_at);
        $this->assertSame('Insufficient workload coverage', $steps[0]->notes);

        // Step 2 is marked SKIPPED so no step remains PENDING
        $this->assertSame(ApprovalStepStatus::SKIPPED, $steps[1]->status);
        $this->assertFalse($app->approvalSteps()->where('status', ApprovalStepStatus::PENDING)->exists());
    }

    /**
     * Observation #9: Single unified Approve action visibility and removal of duplicate Approve Step from table.
     */
    public function test_observation_9_unifies_approve_visibility_and_removes_duplicate_table_action(): void
    {
        $branch = $this->createTestBranch('Branch Obs 9');
        [$managerUser, $managerEmp] = $this->createTestUserAndEmployee('Manager Obs 9', $branch->id);
        [$otherUser, $otherEmp] = $this->createTestUserAndEmployee('Other User Obs 9', $branch->id);
        [$empUser, $employee] = $this->createTestUserAndEmployee('Employee Obs 9', $branch->id, $managerEmp->id);

        ApprovalPolicy::query()->create([
            'name' => 'Visibility Policy',
            'approvable_type' => EmployeeApplicationV2::class,
            'application_type_id' => EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST,
            'branch_ids' => [$branch->id],
            'mode' => ApprovalMode::DIRECT_MANAGER,
            'active' => true,
            'priority' => 1,
        ]);

        $app = $this->createLeaveApplication($employee, $empUser);

        // Current step approver sees the Leave Request action, not other type actions
        $this->actingAs($managerUser);
        $this->assertTrue(EmployeeApplicationResource::canCurrentUserActOnApplication(
            $app,
            EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST
        ));
        $this->assertFalse(EmployeeApplicationResource::canCurrentUserActOnApplication(
            $app,
            EmployeeApplicationV2::APPLICATION_TYPE_ADVANCE_REQUEST
        ));

        // Non-approver does NOT see the action
        $this->actingAs($otherUser);
        $this->assertFalse(EmployeeApplicationResource::canCurrentUserActOnApplication(
            $app,
            EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST
        ));

        // Verify EmployeeApplicationTable no longer registers approvePolicyStepAction
        $tableSource = file_get_contents(app_path('Filament/Clusters/HRApplicationsCluster/Resources/EmployeeApplicationResource/Table/EmployeeApplicationTable.php'));
        $this->assertStringNotContainsString('approvePolicyStepAction', $tableSource);
    }

    /**
     * Observation #10: Recording the actual approver user and employee when a step is assigned to a Role.
     */
    public function test_observation_10_records_actual_approver_for_role_assigned_step(): void
    {
        $branch = $this->createTestBranch('Branch Obs 10');
        [$roleApproverUser, $roleApproverEmp] = $this->createTestUserAndEmployee('Actual Role Approver', $branch->id);
        [$empUser, $employee] = $this->createTestUserAndEmployee('Employee Obs 10', $branch->id);

        $role = Role::query()->create([
            'name' => 'Finance_Approver_Role_' . uniqid(),
            'guard_name' => 'web',
        ]);
        $roleApproverUser->assignRole($role);
        ApprovalWorkflowService::clearCache();

        $policy = ApprovalPolicy::query()->create([
            'name' => 'Role Audit Policy',
            'approvable_type' => EmployeeApplicationV2::class,
            'application_type_id' => EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST,
            'branch_ids' => [$branch->id],
            'mode' => ApprovalMode::CONFIGURED_STEPS,
            'active' => true,
            'priority' => 1,
        ]);

        $policy->policySteps()->create([
            'step_order' => 1,
            'approver_type' => ApprovalPolicyStepType::CUSTOM_ROLE,
            'approver_role_id' => $role->id,
        ]);

        $app = $this->createLeaveApplication($employee, $empUser);

        /** @var ApprovalStep $stepBefore */
        $stepBefore = $app->approvalSteps()->first();
        $this->assertSame($role->id, $stepBefore->approver_role_id);
        $this->assertNull($stepBefore->approver_user_id);
        $this->assertNull($stepBefore->approver_employee_id);

        app(EmployeeApplicationService::class)->approveApplication($app->id, $roleApproverUser->id);

        $stepAfter = $stepBefore->fresh(['approverUser', 'approverEmployee', 'approverRole']);
        $this->assertSame(ApprovalStepStatus::APPROVED, $stepAfter->status);
        $this->assertSame($role->id, $stepAfter->approver_role_id);
        $this->assertSame($roleApproverUser->id, $stepAfter->approver_user_id);
        $this->assertSame($roleApproverEmp->id, $stepAfter->approver_employee_id);
        $this->assertSame('Actual Role Approver', $stepAfter->approverEmployee->name);
    }

    /**
     * Skip-Steps Feature: Allowing a subsequent step approver (e.g. Second Manager)
     * to approve and bypass a pending preceding step when `allow_skip_steps` is enabled.
     */
    public function test_allow_skip_steps_enables_second_manager_to_approve_when_first_manager_has_not_approved(): void
    {
        $branch = $this->createTestBranch('Branch Skip Feature');
        [$firstManagerUser, $firstManagerEmp] = $this->createTestUserAndEmployee('First Direct Manager', $branch->id);
        [$secondManagerUser, $secondManagerEmp] = $this->createTestUserAndEmployee('Second Higher Manager', $branch->id);
        [$empUser, $employee] = $this->createTestUserAndEmployee('Employee Skip Feature', $branch->id, $firstManagerEmp->id);

        $policy = ApprovalPolicy::query()->create([
            'name' => 'Skip Steps Policy',
            'approvable_type' => EmployeeApplicationV2::class,
            'application_type_id' => EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST,
            'branch_ids' => [$branch->id],
            'mode' => ApprovalMode::CONFIGURED_STEPS,
            'active' => true,
            'allow_skip_steps' => false,
        ]);

        $policy->policySteps()->createMany([
            [
                'step_order' => 1,
                'approver_type' => ApprovalPolicyStepType::DIRECT_MANAGER,
            ],
            [
                'step_order' => 2,
                'approver_type' => ApprovalPolicyStepType::CUSTOM_USER,
                'approver_user_id' => $secondManagerUser->id,
            ],
        ]);

        $app = $this->createLeaveApplication($employee, $empUser);
        $workflowService = app(ApprovalWorkflowService::class);
        $appService = app(EmployeeApplicationService::class);

        // 1. While allow_skip_steps is false, Second Manager CANNOT approve while Step 1 is pending
        $this->assertTrue($workflowService->canUserApprove($app, $firstManagerUser));
        $this->assertFalse($workflowService->canUserApprove($app, $secondManagerUser));

        try {
            $appService->approveApplication($app->id, $secondManagerUser->id);
            $this->fail('Second manager should not be able to bypass Step 1 when allow_skip_steps is false.');
        } catch (AuthorizationException $e) {
            $this->assertStringContainsString('Waiting for First Direct Manager to approve step #1', $e->getMessage());
        }

        // 2. Enable allow_skip_steps on the policy -> Observer syncs can_be_skipped on pending steps
        $policy->update(['allow_skip_steps' => true]);
        $app->unsetRelation('approvalSteps');

        $this->assertTrue($workflowService->canUserApprove($app, $firstManagerUser));
        $this->assertTrue($workflowService->canUserApprove($app, $secondManagerUser));

        // 3. Second Manager approves directly while First Manager is absent
        $appService->approveApplication($app->id, $secondManagerUser->id);

        $app->refresh();
        $this->assertSame(EmployeeApplicationV2::STATUS_APPROVED, $app->status);

        $steps = $app->approvalSteps()->orderBy('step_order')->get();
        $this->assertCount(2, $steps);

        // Step 1 is automatically marked SKIPPED with explanatory note
        $this->assertSame(ApprovalStepStatus::SKIPPED, $steps[0]->status);
        $this->assertStringContainsString('Skipped: Approved by step #2', (string) $steps[0]->notes);

        // Step 2 is marked APPROVED by Second Manager
        $this->assertSame(ApprovalStepStatus::APPROVED, $steps[1]->status);
        $this->assertSame($secondManagerUser->id, $steps[1]->approver_user_id);

        // 4. Rolling back approval resets both Step 1 and Step 2 back to PENDING
        $appService->undoApproveApplication($app->id, $secondManagerUser->id);
        $stepsAfterRollback = $app->approvalSteps()->orderBy('step_order')->get();
        $this->assertSame(ApprovalStepStatus::PENDING, $stepsAfterRollback[0]->status);
        $this->assertSame(ApprovalStepStatus::PENDING, $stepsAfterRollback[1]->status);
    }

    /**
     * Architecture & Refactoring Verification:
     * Tests Custom Eloquent Builders (ApprovalPolicyBuilder, ApprovalStepBuilder),
     * ApprovalSubjectRegistry, and HasApprovalWorkflow fluent domain methods.
     */
    public function test_custom_builders_scopes_and_fluent_workflow_trait_methods(): void
    {
        $branch = $this->createTestBranch('Branch Builders Test');
        [$managerUser, $managerEmp] = $this->createTestUserAndEmployee('Manager Builder Test', $branch->id);
        [$empUser, $employee] = $this->createTestUserAndEmployee('Employee Builder Test', $branch->id, $managerEmp->id);

        // 1. Test ApprovalSubjectRegistry encoding/decoding
        $encoded = ApprovalSubjectRegistry::encodeCombinedSubject(
            EmployeeApplicationV2::class,
            EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST
        );
        $this->assertSame(EmployeeApplicationV2::class . ':1', $encoded);
        $this->assertSame(
            [EmployeeApplicationV2::class, EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST],
            ApprovalSubjectRegistry::decodeCombinedSubject($encoded)
        );
        $this->assertSame(
            [EmployeeApplicationV2::class, null],
            ApprovalSubjectRegistry::decodeCombinedSubject(EmployeeApplicationV2::class . ':all')
        );

        // 2. Test ApprovalPolicyBuilder scopes
        $policy = ApprovalPolicy::query()->create([
            'name' => 'Builder Scoped Policy',
            'approvable_type' => EmployeeApplicationV2::class,
            'application_type_id' => EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST,
            'branch_ids' => [$branch->id],
            'mode' => ApprovalMode::DIRECT_MANAGER,
            'active' => true,
            'allow_skip_steps' => false,
        ]);

        $this->assertTrue(
            ApprovalPolicy::query()
                ->active()
                ->forSubject(EmployeeApplicationV2::class, EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST)
                ->forBranch($branch->id)
                ->whereKey($policy->id)
                ->exists()
        );

        // 3. Test HasApprovalWorkflow fluent methods & ApprovalStepBuilder scopes
        $app = $this->createLeaveApplication($employee, $empUser);

        $this->assertTrue($app->requiresApprovalWorkflow());
        $this->assertTrue($app->isSelfApprovalBy($empUser));
        $this->assertFalse($app->canBeApprovedBy($empUser));
        $this->assertTrue($app->canBeApprovedBy($managerUser));

        $step = $app->approvalSteps()->pending()->inOrder()->first();
        $this->assertNotNull($step);
        $this->assertTrue($step->isPending());
        $this->assertSame('Manager Builder Test', $step->approverDisplayName());

        // Approve via fluent trait method
        $app->approveViaWorkflow($managerUser, 'Approved via fluent trait');
        $this->assertSame(EmployeeApplicationV2::STATUS_APPROVED, $app->fresh()->status);
        $this->assertTrue($step->fresh()->isApproved());
        $this->assertFalse($app->fresh()->requiresApprovalWorkflow());

        // Reset via fluent trait method
        $app->resetApprovalWorkflowOnRollback();
        $this->assertTrue($step->fresh()->isPending());
        $this->assertTrue($app->fresh()->requiresApprovalWorkflow());
    }
}
