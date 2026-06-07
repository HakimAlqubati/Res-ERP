<?php

namespace App\Observers;

use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Modules\HR\Leaves\InitEmployeeLeaves\Init;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class LeaveTypeObserver
{
    /**
     * Handle the LeaveType "updating" event.
     *
     * Prevents changing `count_days` when employee leave balances are already
     * linked to this leave type, since it would cause data inconsistency.
     *
     * @throws ValidationException
     */
    public function updating(LeaveType $leaveType): void
    {
        if ($leaveType->isDirty('count_days')) {
            $hasBalances = LeaveBalance::where('leave_type_id', $leaveType->id)->exists();

            if ($hasBalances) {
                throw ValidationException::withMessages([
                    'count_days' => 'Cannot change "Days Count": this leave type already has employee balances linked to it. Update is not allowed.',
                ]);
            }
        }
    }

    /**
     * Handle the LeaveType "created" event.
     *
     * When a new leave type is created, initialize balances for all eligible
     * active employees according to the leave type's eligibility constraints
     * (branch, applicability, etc.).
     *
     * عند إنشاء نوع إجازة جديد، يتم تهيئة أرصدة الإجازة لجميع الموظفين النشطين
     * المؤهلين وفقاً لشروط هذا النوع (الفرع، الفئة المستهدفة، إلخ).
     */
    public function created(LeaveType $leaveType): void
    {
        // نتجاهل أنواع الإجازات غير النشطة أو ذات الأنواع غير المدعومة (weekly, special)
        // Skip inactive leave types or unsupported types (weekly, special)
        if (
            ! $leaveType->active ||
            ! in_array($leaveType->type, [LeaveType::TYPE_YEARLY, LeaveType::TYPE_MONTHLY], true)
        ) {
            Log::info("LeaveTypeObserver: skipped init for leave type [{$leaveType->id}] — inactive or unsupported type.");
            return;
        }

        Log::info("LeaveTypeObserver: initializing balances for new leave type [{$leaveType->id}] ({$leaveType->name}).");

        (new Init())->handleForNewLeaveType($leaveType);
    }
}
