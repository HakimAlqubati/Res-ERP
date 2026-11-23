<?php

declare(strict_types=1);

namespace Tests\Feature\HR;

use App\Enums\DayOfWeek;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeOvertime;
use App\Models\EmployeePeriodHistory;
use App\Models\Payroll;
use App\Models\User;
use App\Models\WorkPeriod;
use App\Modules\HR\Attendance\Enums\AttendanceStatus;
use App\Modules\HR\AttendanceReports\Services\EmployeeAttendanceRangeService;
use App\Modules\HR\Overtime\OvertimeService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MultiShiftOvertimeTest extends TestCase
{
    use DatabaseTransactions;

    private function createTestContext(string $suffix = 'OT'): array
    {
        $user = User::create([
            'name'     => "OT Manager {$suffix}",
            'email'    => "ot_mgr_{$suffix}_" . Str::random(6) . '@test.local',
            'password' => bcrypt('secret123'),
            'active'   => 1,
        ]);

        $this->actingAs($user);

        $branch = Branch::create([
            'name'       => "OT Branch {$suffix} " . Str::random(4),
            'manager_id' => $user->id,
            'active'     => true,
            'type'       => Branch::TYPE_BRANCH,
        ]);

        $user->updateQuietly(['branch_id' => $branch->id]);

        $employee = Employee::withoutEvents(function () use ($user, $branch, $suffix) {
            return Employee::create([
                'name'          => "OT Emp {$suffix} " . Str::random(5),
                'email'         => "ot_emp_{$suffix}_" . Str::random(6) . '@test.local',
                'user_id'       => $user->id,
                'branch_id'     => $branch->id,
                'active'        => 1,
                'join_date'     => '2025-01-01',
                'salary'        => 4000,
                'working_hours' => 8,
                'working_days'  => 26,
            ]);
        });

        $shift1 = WorkPeriod::create([
            'name'              => "Morning Shift {$suffix}",
            'start_at'          => '08:00:00',
            'end_at'            => '12:00:00',
            'supposed_duration' => '04:00',
            'active'            => 1,
            'day_and_night'     => 0,
        ]);

        $shift2 = WorkPeriod::create([
            'name'              => "Evening Shift {$suffix}",
            'start_at'          => '16:00:00',
            'end_at'            => '20:00:00',
            'supposed_duration' => '04:00',
            'active'            => 1,
            'day_and_night'     => 0,
        ]);

        // Assign both shifts across all 7 days of the week (14 rows in hr_employee_period_histories)
        foreach (DayOfWeek::cases() as $dayCase) {
            EmployeePeriodHistory::create([
                'employee_id' => $employee->id,
                'period_id'   => $shift1->id,
                'branch_id'   => $branch->id,
                'start_date'  => '2025-01-01',
                'end_date'    => null,
                'start_time'  => '08:00:00',
                'end_time'    => '12:00:00',
                'active'      => 1,
                'day_of_week' => $dayCase->value,
            ]);

            EmployeePeriodHistory::create([
                'employee_id' => $employee->id,
                'period_id'   => $shift2->id,
                'branch_id'   => $branch->id,
                'start_date'  => '2025-01-01',
                'end_date'    => null,
                'start_time'  => '16:00:00',
                'end_time'    => '20:00:00',
                'active'      => 1,
                'day_of_week' => $dayCase->value,
            ]);
        }

        return [$user, $branch, $employee, $shift1, $shift2];
    }

    private function seedShiftAttendance(
        Employee $employee,
        ?int $periodId,
        string $date,
        string $checkInTime,
        string $checkOutTime,
        string $status = 'on_time',
        ?string $checkOutDate = null
    ): void {
        $outDate = $checkOutDate ?? $date;

        Attendance::withoutEvents(function () use ($employee, $periodId, $date, $checkInTime, $checkOutTime, $status, $outDate) {
            Attendance::create([
                'employee_id'     => $employee->id,
                'branch_id'       => $employee->branch_id,
                'period_id'       => $periodId,
                'check_date'      => $date,
                'real_check_date' => $date,
                'check_time'      => $checkInTime,
                'check_type'      => Attendance::CHECKTYPE_CHECKIN,
                'status'          => $status,
                'accepted'        => 1,
                'created_by'      => auth()->id(),
            ]);

            Attendance::create([
                'employee_id'     => $employee->id,
                'branch_id'       => $employee->branch_id,
                'period_id'       => $periodId,
                'check_date'      => $date,
                'real_check_date' => $outDate,
                'check_time'      => $checkOutTime,
                'check_type'      => Attendance::CHECKTYPE_CHECKOUT,
                'status'          => $status,
                'accepted'        => 1,
                'created_by'      => auth()->id(),
            ]);
        });
    }

    public function test_1_suggests_both_shifts_without_7x_weekly_duplication(): void
    {
        [$user, $branch, $employee, $shift1, $shift2] = $this->createTestContext('S1');
        $this->actingAs($user);
        $date = '2026-10-15';

        // Shift 1: 08:00 -> 14:00 (6h worked vs 4h supposed = 2h OT)
        $this->seedShiftAttendance($employee, $shift1->id, $date, '08:00:00', '14:00:00');
        // Shift 2: 16:00 -> 22:30 (6.5h worked vs 4h supposed = 2.5h OT)
        $this->seedShiftAttendance($employee, $shift2->id, $date, '16:00:00', '22:30:00');

        // Classic calculation
        $classicResults = $employee->calculateEmployeeOvertime($employee, $date);
        $this->assertCount(2, $classicResults, 'Classic calculation must return exactly 2 shift suggestions (not 1 and not 14).');
        $this->assertSame($shift1->id, $classicResults[0]['period_id']);
        $this->assertSame($shift2->id, $classicResults[1]['period_id']);
        $this->assertEquals(2.0, (float) $classicResults[0]['overtime_hours']);
        $this->assertEquals(2.5, (float) $classicResults[1]['overtime_hours']);

        // V3 in-memory suggestion via OvertimeService
        $service = app(OvertimeService::class);
        $v3Suggestions = $service->getSuggestedOvertimeV3($date, $date, $branch->id);

        $this->assertArrayHasKey($date, $v3Suggestions);
        $this->assertCount(2, $v3Suggestions[$date], 'V3 must return both shift suggestions without 7x duplication.');
        $this->assertSame($shift1->id, $v3Suggestions[$date][0]['period_id']);
        $this->assertSame($shift2->id, $v3Suggestions[$date][1]['period_id']);
    }

    public function test_2_creates_and_stores_overtime_for_both_shifts_on_same_day(): void
    {
        [$user, $branch, $employee, $shift1, $shift2] = $this->createTestContext('S2');
        $this->actingAs($user);
        $date = '2026-10-16';

        $ot1 = EmployeeOvertime::create([
            'employee_id' => $employee->id,
            'branch_id'   => $branch->id,
            'date'        => $date,
            'period_id'   => $shift1->id,
            'start_time'  => '08:00:00',
            'end_time'    => '14:00:00',
            'hours'       => 2.0,
            'type'        => EmployeeOvertime::TYPE_BASED_ON_DAY,
            'status'      => EmployeeOvertime::STATUS_PENDING,
        ]);

        $ot2 = EmployeeOvertime::create([
            'employee_id' => $employee->id,
            'branch_id'   => $branch->id,
            'date'        => $date,
            'period_id'   => $shift2->id,
            'start_time'  => '16:00:00',
            'end_time'    => '22:00:00',
            'hours'       => 2.0,
            'type'        => EmployeeOvertime::TYPE_BASED_ON_DAY,
            'status'      => EmployeeOvertime::STATUS_PENDING,
        ]);

        $this->assertNotNull($ot1->id);
        $this->assertNotNull($ot2->id);
        $this->assertSame($shift1->id, (int) $ot1->period_id);
        $this->assertSame($shift2->id, (int) $ot2->period_id);

        $count = EmployeeOvertime::where('employee_id', $employee->id)
            ->whereDate('date', $date)
            ->count();
        $this->assertSame(2, $count);
    }

    public function test_3_blocks_duplicate_overtime_for_same_shift_on_same_day(): void
    {
        [$user, $branch, $employee, $shift1] = $this->createTestContext('S3');
        $this->actingAs($user);
        $date = '2026-10-17';

        EmployeeOvertime::create([
            'employee_id' => $employee->id,
            'branch_id'   => $branch->id,
            'date'        => $date,
            'period_id'   => $shift1->id,
            'start_time'  => '08:00:00',
            'end_time'    => '14:00:00',
            'hours'       => 2.0,
            'type'        => EmployeeOvertime::TYPE_BASED_ON_DAY,
            'status'      => EmployeeOvertime::STATUS_PENDING,
        ]);

        $this->expectException(ValidationException::class);

        EmployeeOvertime::create([
            'employee_id' => $employee->id,
            'branch_id'   => $branch->id,
            'date'        => $date,
            'period_id'   => $shift1->id,
            'start_time'  => '08:00:00',
            'end_time'    => '15:00:00',
            'hours'       => 3.0,
            'type'        => EmployeeOvertime::TYPE_BASED_ON_DAY,
            'status'      => EmployeeOvertime::STATUS_PENDING,
        ]);
    }

    public function test_4_auto_resolves_period_id_from_hi_start_time_and_deduplicates_in_request_and_counts_skipped(): void
    {
        [$user, $branch, $employee, $shift1, $shift2] = $this->createTestContext('S4');
        $this->actingAs($user);
        $date = '2026-10-18';

        // Attendance has seconds (08:03:45 and 16:04:19)
        $this->seedShiftAttendance($employee, $shift1->id, $date, '08:03:45', '14:10:00');
        $this->seedShiftAttendance($employee, $shift2->id, $date, '16:04:19', '22:15:00');

        $service = app(OvertimeService::class);

        // First call: store Shift 1 only (without passing period_id, start_time in H:i format, plus an in-request duplicate)
        $res1 = $service->storeBulkOvertime([
            'type'      => EmployeeOvertime::TYPE_BASED_ON_DAY,
            'date'      => $date,
            'branch_id' => $branch->id,
            'employees' => [
                [
                    'employee_id' => $employee->id,
                    'start_time'  => '08:03',
                    'end_time'    => '14:10',
                    'hours'       => 2.0,
                    'notes'       => 'Shift 1 via API without period_id',
                ],
                [
                    'employee_id' => $employee->id,
                    'start_time'  => '08:03',
                    'end_time'    => '14:10',
                    'hours'       => 2.0,
                    'notes'       => 'Accidental duplicate in same request',
                ],
            ],
        ]);

        $this->assertTrue($res1['status']);
        $this->assertSame(1, $res1['count']);

        $savedShift1 = EmployeeOvertime::where('employee_id', $employee->id)->whereDate('date', $date)->first();
        $this->assertNotNull($savedShift1);
        $this->assertSame($shift1->id, (int) $savedShift1->period_id);

        // Second call: send Shift 1 (already exists) + Shift 2 (new) without period_id
        $res2 = $service->storeBulkOvertime([
            'type'      => EmployeeOvertime::TYPE_BASED_ON_DAY,
            'date'      => $date,
            'branch_id' => $branch->id,
            'employees' => [
                [
                    'employee_id' => $employee->id,
                    'start_time'  => '08:03',
                    'end_time'    => '14:10',
                    'hours'       => 2.0,
                    'notes'       => 'Shift 1 duplicate',
                ],
                [
                    'employee_id' => $employee->id,
                    'start_time'  => '16:04',
                    'end_time'    => '22:15',
                    'hours'       => 2.25,
                    'notes'       => 'Shift 2 new',
                ],
            ],
        ]);

        $this->assertTrue($res2['status']);
        $this->assertSame(1, $res2['count'], 'Only Shift 2 should be created.');
        $this->assertStringContainsString('Skipped 1 existing records', $res2['message']);

        // Third call: send only Shift 2 on another day where Shift 1 is already saved -> skippedCount must be 0!
        $date2 = '2026-10-19';
        $this->seedShiftAttendance($employee, $shift1->id, $date2, '08:00:10', '14:00:00');
        $this->seedShiftAttendance($employee, $shift2->id, $date2, '16:00:20', '22:00:00');

        EmployeeOvertime::create([
            'employee_id' => $employee->id,
            'branch_id'   => $branch->id,
            'date'        => $date2,
            'period_id'   => $shift1->id,
            'start_time'  => '08:00:00',
            'end_time'    => '14:00:00',
            'hours'       => 2.0,
            'type'        => EmployeeOvertime::TYPE_BASED_ON_DAY,
            'status'      => EmployeeOvertime::STATUS_PENDING,
        ]);

        $res3 = $service->storeBulkOvertime([
            'type'      => EmployeeOvertime::TYPE_BASED_ON_DAY,
            'date'      => $date2,
            'branch_id' => $branch->id,
            'employees' => [
                [
                    'employee_id' => $employee->id,
                    'start_time'  => '16:00',
                    'end_time'    => '22:00',
                    'hours'       => 2.0,
                    'notes'       => 'Only Shift 2 submitted',
                ],
            ],
        ]);

        $this->assertSame(1, $res3['count']);
        $this->assertSame('Overtime records created successfully', $res3['message'], 'Should not report skipped records when only Shift 2 was submitted.');
    }

    public function test_5_resolves_cross_midnight_shift_using_24h_circular_distance(): void
    {
        [$user, $branch, $employee, $shift1] = $this->createTestContext('S5');
        $this->actingAs($user);
        $date = '2026-10-20';

        $nightShift = WorkPeriod::create([
            'name'              => 'Late Night Shift',
            'start_at'          => '23:55:00',
            'end_at'            => '03:55:00',
            'supposed_duration' => '04:00',
            'active'            => 1,
            'day_and_night'     => 1,
        ]);

        EmployeePeriodHistory::create([
            'employee_id' => $employee->id,
            'period_id'   => $nightShift->id,
            'branch_id'   => $branch->id,
            'start_date'  => '2025-01-01',
            'end_date'    => null,
            'start_time'  => '23:55:00',
            'end_time'    => '03:55:00',
            'active'      => 1,
            'day_of_week' => DayOfWeek::Tuesday->value,
        ]);

        // Check-in happened at 23:55, manual overtime start_time passed as 00:05 (10 mins circular distance vs 475 mins to 08:00)
        $this->seedShiftAttendance($employee, $shift1->id, $date, '08:00:00', '12:00:00');
        $this->seedShiftAttendance($employee, $nightShift->id, $date, '23:55:00', '05:30:00', 'on_time', '2026-10-21');

        $resolvedId = EmployeeOvertime::resolvePeriodId($employee->id, $date, '00:05');
        $this->assertSame($nightShift->id, $resolvedId);
    }

    public function test_6_skips_already_recorded_pending_or_rejected_shift_when_suggesting_second_shift(): void
    {
        [$user, $branch, $employee, $shift1, $shift2] = $this->createTestContext('S6');
        $this->actingAs($user);
        $date = '2026-10-21';

        $this->seedShiftAttendance($employee, $shift1->id, $date, '08:00:00', '14:00:00');
        $this->seedShiftAttendance($employee, $shift2->id, $date, '16:00:00', '22:00:00');

        // Record Shift 1 as PENDING
        $ot1 = EmployeeOvertime::create([
            'employee_id' => $employee->id,
            'branch_id'   => $branch->id,
            'date'        => $date,
            'period_id'   => $shift1->id,
            'start_time'  => '08:00:00',
            'end_time'    => '14:00:00',
            'hours'       => 2.0,
            'type'        => EmployeeOvertime::TYPE_BASED_ON_DAY,
            'status'      => EmployeeOvertime::STATUS_PENDING,
        ]);

        $service = app(OvertimeService::class);
        $suggestionsPending = $service->getSuggestedOvertimeV3($date, $date, $branch->id);

        $this->assertCount(1, $suggestionsPending[$date], 'Should only suggest Shift 2 when Shift 1 is pending.');
        $this->assertSame($shift2->id, $suggestionsPending[$date][0]['period_id']);

        // Change Shift 1 to REJECTED -> should still only suggest Shift 2
        $ot1->update(['status' => EmployeeOvertime::STATUS_REJECTED]);
        $suggestionsRejected = $service->getSuggestedOvertimeV3($date, $date, $branch->id);

        $this->assertCount(1, $suggestionsRejected[$date], 'Should only suggest Shift 2 when Shift 1 is rejected.');
        $this->assertSame($shift2->id, $suggestionsRejected[$date][0]['period_id']);
    }

    public function test_7_payroll_overtimes_relationship_only_sums_approved_shifts(): void
    {
        [$user, $branch, $employee, $shift1, $shift2] = $this->createTestContext('S7');
        $this->actingAs($user);
        $date = '2026-10-22';

        $ot1 = EmployeeOvertime::create([
            'employee_id' => $employee->id,
            'branch_id'   => $branch->id,
            'date'        => $date,
            'period_id'   => $shift1->id,
            'start_time'  => '08:00:00',
            'end_time'    => '14:00:00',
            'hours'       => 2.0,
            'type'        => EmployeeOvertime::TYPE_BASED_ON_DAY,
            'status'      => EmployeeOvertime::STATUS_APPROVED,
        ]);

        $ot2 = EmployeeOvertime::create([
            'employee_id' => $employee->id,
            'branch_id'   => $branch->id,
            'date'        => $date,
            'period_id'   => $shift2->id,
            'start_time'  => '16:00:00',
            'end_time'    => '22:30:00',
            'hours'       => 2.5,
            'type'        => EmployeeOvertime::TYPE_BASED_ON_DAY,
            'status'      => EmployeeOvertime::STATUS_PENDING,
        ]);

        $this->assertEquals(2.0, (float) $employee->overtimes()->sum('hours'));
        $this->assertEquals(4.5, (float) $employee->allDayOvertimes()->sum('hours'));

        $ot2->update(['status' => EmployeeOvertime::STATUS_APPROVED]);
        $this->assertEquals(4.5, (float) $employee->overtimes()->sum('hours'));
    }

    public function test_8_full_day_overtime_based_on_month_forces_null_period_and_allows_only_one_per_day(): void
    {
        [$user, $branch, $employee, $shift1, $shift2] = $this->createTestContext('S8');
        $this->actingAs($user);
        $date = '2026-10-23';

        $dailyOt = EmployeeOvertime::create([
            'employee_id' => $employee->id,
            'branch_id'   => $branch->id,
            'date'        => $date,
            'period_id'   => $shift1->id, // Even if passed, observer must force null
            'hours'       => 8.0,
            'type'        => EmployeeOvertime::TYPE_BASED_ON_MONTH,
            'status'      => EmployeeOvertime::STATUS_PENDING,
        ]);

        $this->assertNull($dailyOt->period_id, 'TYPE_BASED_ON_MONTH must force period_id to null.');

        $this->expectException(ValidationException::class);

        EmployeeOvertime::create([
            'employee_id' => $employee->id,
            'branch_id'   => $branch->id,
            'date'        => $date,
            'period_id'   => $shift2->id,
            'hours'       => 8.0,
            'type'        => EmployeeOvertime::TYPE_BASED_ON_MONTH,
            'status'      => EmployeeOvertime::STATUS_PENDING,
        ]);
    }

    public function test_9_soft_delete_allows_recreation_and_restore_blocks_duplicate_or_locked_payroll(): void
    {
        [$user, $branch, $employee, $shift1] = $this->createTestContext('S9');
        $this->actingAs($user);
        $date = '2026-10-24';

        $oldOt = EmployeeOvertime::create([
            'employee_id' => $employee->id,
            'branch_id'   => $branch->id,
            'date'        => $date,
            'period_id'   => $shift1->id,
            'start_time'  => '08:00:00',
            'end_time'    => '14:00:00',
            'hours'       => 2.0,
            'type'        => EmployeeOvertime::TYPE_BASED_ON_DAY,
            'status'      => EmployeeOvertime::STATUS_PENDING,
        ]);

        // Soft delete allows creating a replacement for the same shift
        $oldOt->delete();

        $newOt = EmployeeOvertime::create([
            'employee_id' => $employee->id,
            'branch_id'   => $branch->id,
            'date'        => $date,
            'period_id'   => $shift1->id,
            'start_time'  => '08:00:00',
            'end_time'    => '15:00:00',
            'hours'       => 3.0,
            'type'        => EmployeeOvertime::TYPE_BASED_ON_DAY,
            'status'      => EmployeeOvertime::STATUS_PENDING,
        ]);
        $this->assertNotNull($newOt->id);

        // Restoring old soft-deleted record while new replacement is active must fail
        $restoreBlockedByDuplicate = false;
        try {
            $oldOt->restore();
        } catch (ValidationException $e) {
            $restoreBlockedByDuplicate = true;
        }
        $this->assertTrue($restoreBlockedByDuplicate, 'Restoring a soft-deleted record over an active duplicate must throw ValidationException.');

        // Now soft-delete the new one too, lock payroll for October 2026, and verify restore is blocked by PayrollLockGuard
        $newOt->delete();
        Payroll::withoutEvents(function () use ($employee, $branch, $user) {
            $run = \App\Models\PayrollRun::withoutEvents(function () use ($branch, $user) {
                return \App\Models\PayrollRun::create([
                    'branch_id'         => $branch->id,
                    'year'              => 2026,
                    'month'             => 10,
                    'name'              => 'Oct 2026 Run',
                    'period_start_date' => '2026-10-01',
                    'period_end_date'   => '2026-10-31',
                    'status'            => 'approved',
                    'created_by'        => $user->id,
                ]);
            });

            return Payroll::create([
                'payroll_run_id'    => $run->id,
                'employee_id'       => $employee->id,
                'branch_id'         => $branch->id,
                'year'              => 2026,
                'month'             => 10,
                'period_start_date' => '2026-10-01',
                'period_end_date'   => '2026-10-31',
                'base_salary'       => 4000,
                'gross_salary'      => 4000,
                'net_salary'        => 4000,
                'status'            => 'approved',
                'created_by'        => $user->id,
            ]);
        });

        $restoreBlockedByPayroll = false;
        try {
            $newOt->restore();
        } catch (ValidationException $e) {
            $restoreBlockedByPayroll = true;
        }
        $this->assertTrue($restoreBlockedByPayroll, 'Restoring a soft-deleted overtime in a locked payroll month must throw ValidationException.');
    }

    public function test_10_approve_reject_undo_and_update_hours_work_smoothly_when_two_shifts_exist(): void
    {
        [$user, $branch, $employee, $shift1, $shift2] = $this->createTestContext('S10');
        $this->actingAs($user);
        $date = '2026-10-25';

        $ot1 = EmployeeOvertime::create([
            'employee_id' => $employee->id,
            'branch_id'   => $branch->id,
            'date'        => $date,
            'period_id'   => $shift1->id,
            'start_time'  => '08:00:00',
            'end_time'    => '14:00:00',
            'hours'       => 2.0,
            'type'        => EmployeeOvertime::TYPE_BASED_ON_DAY,
            'status'      => EmployeeOvertime::STATUS_PENDING,
        ]);

        $ot2 = EmployeeOvertime::create([
            'employee_id' => $employee->id,
            'branch_id'   => $branch->id,
            'date'        => $date,
            'period_id'   => $shift2->id,
            'start_time'  => '16:00:00',
            'end_time'    => '22:00:00',
            'hours'       => 2.0,
            'type'        => EmployeeOvertime::TYPE_BASED_ON_DAY,
            'status'      => EmployeeOvertime::STATUS_PENDING,
        ]);

        $service = app(OvertimeService::class);

        // Update hours on Shift 1
        $updated = $service->updateHours($ot1->id, 2.75);
        $this->assertEquals(2.75, (float) $updated->hours);

        // Approve both shifts
        $service->approve([$ot1->id, $ot2->id]);
        $this->assertSame(EmployeeOvertime::STATUS_APPROVED, $ot1->fresh()->status);
        $this->assertSame(EmployeeOvertime::STATUS_APPROVED, $ot2->fresh()->status);

        // Undo approval on Shift 2 and reject it
        $service->undoApproval($ot2->id);
        $this->assertSame(EmployeeOvertime::STATUS_PENDING, $ot2->fresh()->status);

        $service->reject($ot2->id);
        $this->assertSame(EmployeeOvertime::STATUS_REJECTED, $ot2->fresh()->status);
    }

    public function test_11_no_shift_attendance_coexists_with_regular_shift_and_preserves_null_period_id(): void
    {
        [$user, $branch, $employee, $shift1] = $this->createTestContext('S11');
        $this->actingAs($user);
        $date = '2026-10-26';

        // Shift 1 attendance (08:00 -> 14:00 = 2h OT)
        $this->seedShiftAttendance($employee, $shift1->id, $date, '08:00:00', '14:00:00');
        // No-shift attendance in the evening (20:30 -> 23:00 = 2.5h OT, period_id = null, status = no_shift)
        $this->seedShiftAttendance($employee, null, $date, '20:30:00', '23:00:00', AttendanceStatus::NO_SHIFT->value);

        // Auto-resolver must return NULL for start_time = '20:30', NOT attach shift1->id
        $resolvedForNoShift = EmployeeOvertime::resolvePeriodId($employee->id, $date, '20:30');
        $this->assertNull($resolvedForNoShift, 'NO_SHIFT check-in must resolve to null period_id.');

        $service = app(OvertimeService::class);
        $suggestions = $service->getSuggestedOvertimeV3($date, $date, $branch->id);

        $this->assertCount(2, $suggestions[$date], 'Should suggest both Shift 1 overtime and No-Shift overtime.');

        // Store both via storeBulkOvertime
        $storeRes = $service->storeBulkOvertime([
            'type'      => EmployeeOvertime::TYPE_BASED_ON_DAY,
            'date'      => $date,
            'branch_id' => $branch->id,
            'employees' => $suggestions[$date],
        ]);

        $this->assertSame(2, $storeRes['count']);
    }

    public function test_12_general_manual_overtime_without_no_shift_blocks_shift_duplicate_and_autoprocess(): void
    {
        [$user, $branch, $employee, $shift1, $shift2] = $this->createTestContext('S12');
        $this->actingAs($user);
        $date = '2026-10-27';

        $this->seedShiftAttendance($employee, $shift1->id, $date, '08:00:00', '14:00:00');
        $this->seedShiftAttendance($employee, $shift2->id, $date, '16:00:00', '22:00:00');

        // General manual hourly entry without period_id and without start_time (like BatchQuickAdd when employee worked 2 shifts)
        $generalOt = EmployeeOvertime::create([
            'employee_id' => $employee->id,
            'branch_id'   => $branch->id,
            'date'        => $date,
            'period_id'   => null,
            'start_time'  => null,
            'end_time'    => null,
            'hours'       => 3.0,
            'type'        => EmployeeOvertime::TYPE_BASED_ON_DAY,
            'status'      => EmployeeOvertime::STATUS_PENDING,
        ]);

        $this->assertNull($generalOt->period_id);

        // V3 suggestions must skip this day because a general whole-day record exists
        $service = app(OvertimeService::class);
        $suggestions = $service->getSuggestedOvertimeV3($date, $date, $branch->id);
        $this->assertEmpty($suggestions[$date] ?? []);

        // Attempting to add a shift-specific record on top of the general record must be blocked
        $this->expectException(ValidationException::class);
        EmployeeOvertime::create([
            'employee_id' => $employee->id,
            'branch_id'   => $branch->id,
            'date'        => $date,
            'period_id'   => $shift1->id,
            'start_time'  => '08:00:00',
            'end_time'    => '14:00:00',
            'hours'       => 2.0,
            'type'        => EmployeeOvertime::TYPE_BASED_ON_DAY,
            'status'      => EmployeeOvertime::STATUS_PENDING,
        ]);
    }

    public function test_13_attendance_report_prevents_double_counting_across_two_shifts(): void
    {
        [$user, $branch, $employee, $shift1, $shift2] = $this->createTestContext('S13');
        $this->actingAs($user);
        $date = '2026-10-28';

        // Shift 1: 4h supposed, 6h actual (08:00 -> 14:00), 2h approved OT -> approved_overtime should be 06:00:00
        $this->seedShiftAttendance($employee, $shift1->id, $date, '08:00:00', '14:00:00');
        // Shift 2: 4h supposed, 6.5h actual (16:00 -> 22:30), 2.5h approved OT -> approved_overtime should be 06:30:00
        $this->seedShiftAttendance($employee, $shift2->id, $date, '16:00:00', '22:30:00');

        EmployeeOvertime::create([
            'employee_id' => $employee->id,
            'branch_id'   => $branch->id,
            'date'        => $date,
            'period_id'   => $shift1->id,
            'start_time'  => '08:00:00',
            'end_time'    => '14:00:00',
            'hours'       => 2.0,
            'type'        => EmployeeOvertime::TYPE_BASED_ON_DAY,
            'status'      => EmployeeOvertime::STATUS_APPROVED,
        ]);

        EmployeeOvertime::create([
            'employee_id' => $employee->id,
            'branch_id'   => $branch->id,
            'date'        => $date,
            'period_id'   => $shift2->id,
            'start_time'  => '16:00:00',
            'end_time'    => '22:30:00',
            'hours'       => 2.5,
            'type'        => EmployeeOvertime::TYPE_BASED_ON_DAY,
            'status'      => EmployeeOvertime::STATUS_APPROVED,
        ]);

        /** @var EmployeeAttendanceRangeService $rangeService */
        $rangeService = app(EmployeeAttendanceRangeService::class);
        $report = $rangeService->fetchRange($employee, Carbon::parse($date), Carbon::parse($date));

        $dayReport = $report->get($date);
        $this->assertNotNull($dayReport);

        $periods = collect($dayReport['periods'])->keyBy('period_id');
        $p1Approved = $periods->get($shift1->id)['attendances']['checkout']['lastcheckout']['approved_overtime'] ?? null;
        $p2Approved = $periods->get($shift2->id)['attendances']['checkout']['lastcheckout']['approved_overtime'] ?? null;

        $this->assertSame('6h 0m', $p1Approved, 'Shift 1 must only include its own 2h overtime (4h + 2h = 6h 0m).');
        $this->assertSame('6h 30m', $p2Approved, 'Shift 2 must only include its own 2.5h overtime (4h + 2.5h = 6h 30m).');
        $this->assertSame('12:30:00', $report->get('total_approved_overtime'), 'Total approved duration + overtime must not double-count across shifts.');
    }

    public function test_14_pre_loop_resolution_assigns_legacy_null_period_overtime_to_exceeded_second_shift(): void
    {
        [$user, $branch, $employee, $shift1, $shift2] = $this->createTestContext('S14');
        $this->actingAs($user);
        $date = '2026-10-29';

        // Shift 1: Normal 4h worked (08:00 -> 12:00, no overtime)
        $this->seedShiftAttendance($employee, $shift1->id, $date, '08:00:00', '12:00:00');
        // Shift 2: 6h worked (16:00 -> 22:00, 2h overtime)
        $this->seedShiftAttendance($employee, $shift2->id, $date, '16:00:00', '22:00:00');

        // Legacy/unassigned overtime record with period_id = null and start_time = null
        EmployeeOvertime::withoutEvents(function () use ($employee, $branch, $date) {
            return EmployeeOvertime::create([
                'employee_id' => $employee->id,
                'branch_id'   => $branch->id,
                'date'        => $date,
                'period_id'   => null,
                'start_time'  => null,
                'end_time'    => null,
                'hours'       => 2.0,
                'type'        => EmployeeOvertime::TYPE_BASED_ON_DAY,
                'status'      => EmployeeOvertime::STATUS_APPROVED,
            ]);
        });

        /** @var EmployeeAttendanceRangeService $rangeService */
        $rangeService = app(EmployeeAttendanceRangeService::class);
        $report = $rangeService->fetchRange($employee, Carbon::parse($date), Carbon::parse($date));

        $dayReport = $report->get($date);
        $periods = collect($dayReport['periods'])->keyBy('period_id');

        $p1Approved = $periods->get($shift1->id)['attendances']['checkout']['lastcheckout']['approved_overtime'] ?? null;
        $p2Approved = $periods->get($shift2->id)['attendances']['checkout']['lastcheckout']['approved_overtime'] ?? null;

        $this->assertSame('4h 0m', $p1Approved, 'Shift 1 (no overtime worked) must not steal the unassigned overtime record.');
        $this->assertSame('6h 0m', $p2Approved, 'Shift 2 (where overtime was worked) must receive the unassigned 2h overtime.');
    }
}
