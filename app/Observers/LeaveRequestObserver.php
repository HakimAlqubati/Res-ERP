<?php

namespace App\Observers;

use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Observer for LeaveRequest model.
 *
 * يتحقق قبل إنشاء أي طلب إجازة من عدم وجود تداخل مع إجازات سابقة.
 * رمي ValidationException في creating() يُلغي INSERT ويتراجع عن
 * أي transaction محيطة (بما فيها سجل EmployeeApplicationV2 الأب).
 */
class LeaveRequestObserver
{
    /**
     * التحقق من عدم التداخل قبل الحفظ.
     *
     * @throws ValidationException
     */
    public function creating(LeaveRequest $leaveRequest): void
    {
        $startDate = $leaveRequest->start_date;
        $endDate   = $leaveRequest->end_date;

        if (! $startDate || ! $endDate) {
            return;
        }

        $start = Carbon::parse($startDate);
        $end   = Carbon::parse($endDate);

        // البحث عن إجازات متداخلة لنفس الموظف (باستثناء الطلب الحالي إن وُجد)
        $hasOverlap = LeaveRequest::where('employee_id', $leaveRequest->employee_id)
            ->where('start_date', '<=', $end->toDateString())
            ->where('end_date', '>=', $start->toDateString())
            ->when($leaveRequest->application_id, fn ($q) =>
                $q->where('application_id', '!=', $leaveRequest->application_id)
            )
            ->exists();

        if ($hasOverlap) {
            throw ValidationException::withMessages([
                'start_date' => __('notifications.leave_request_overlap',
                    ['default' => 'An approved leave request already exists that overlaps with the selected dates.']
                ),
            ]);
        }
    }
}
