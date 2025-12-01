<?php

namespace App\Services\HR\v2\Attendance\Validators;

use App\Models\Attendance;
use App\Models\Employee;
use App\Services\HR\v2\Attendance\ShiftResolver;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class AttendanceBusinessValidator
{
    public function __construct(
        protected ShiftResolver $shiftResolver
    ) {}

    /**
     * الحارس الرئيسي: يقوم بفحص جميع قواعد العمل.
     *
     * @throws ValidationException
     */
    public function validate(Employee $employee, Carbon $requestTime, ?string $requestType = null): void
    {
        $date = $requestTime->toDateString();

        // 1. جلب السجلات
        $dailyRecords = Attendance::with('period')
            ->where('employee_id', $employee->id)
            ->where('check_date', $date)
            ->where('accepted', 1)
            ->get();

        $checkInRecord = $dailyRecords->firstWhere('check_type', Attendance::CHECKTYPE_CHECKIN);
        $checkOutRecord = $dailyRecords
            ->sortByDesc('check_time')
            ->firstWhere('check_type', Attendance::CHECKTYPE_CHECKOUT);

        // --- القواعد (Business Rules) ---

        // القاعدة 1: اليوم مكتمل (دخول + خروج) -> هل نغلق الباب؟
        if ($checkInRecord && $checkOutRecord) {

            // نحتاج لحساب حدود الوردية لنتخذ القرار
            // نستخدم سجل الدخول كمرجع للوردية
            $bounds = $this->calculateBoundsSafety($checkInRecord);

            if ($bounds) {
                // الشرط أ: هل انتهت فترة السماحية المحددة بعد الدوام؟ (Time Expired)
                $isGraceExpired = $requestTime->gt($bounds['windowEnd']);

                // الشرط ب (الجديد): هل قام الموظف بالخروج بعد انتهاء الدوام الرسمي؟ (Mission Accomplished)
                // إذا خرج بعد الوقت الرسمي، يعتبر دوامه منتهياً فوراً ولا داعي لانتظار فترة السماحية
                $isShiftCompleted = $this->isCheckoutAfterShiftEnd($checkOutRecord, $bounds['end']);

                if ($isGraceExpired || $isShiftCompleted) {
                    $this->throwError(
                        'attendance_completed',
                        __('notifications.attendance_already_completed_for_date', ['date' => $date])
                    );
                }
            }
        }

        // القاعدة 2: تكرار الدخول
        if ($requestType === Attendance::CHECKTYPE_CHECKIN && $checkInRecord) {
            $this->throwError(
                'duplicate_checkin',
                __('notifications.you_are_already_checked_in')
            );
        }

        // القاعدة 3: الخروج بدون دخول
        if ($requestType === Attendance::CHECKTYPE_CHECKOUT && !$checkInRecord) {
            $this->throwError(
                'missing_checkin',
                __('notifications.cannot_checkout_without_checkin')
            );
        }
    }

    /**
     * دالة مساعدة لحساب الحدود بأمان
     */
    protected function calculateBoundsSafety(Attendance $record): ?array
    {
        if (!$record->period) return null;
        return $this->shiftResolver->calculateBounds($record->period, $record->check_date);
    }

    /**
     * التحقق هل وقت الخروج المسجل كان بعد انتهاء الوردية الرسمية
     */
    protected function isCheckoutAfterShiftEnd(Attendance $checkoutRecord, Carbon $shiftEnd): bool
    {
        // يجب تكوين وقت الخروج بدقة (التاريخ الفعلي + الوقت)
        // نستخدم real_check_date إذا توفر لضمان الدقة في الورديات الليلية
        // أو نعتمد على check_date إذا كان النظام لا يخزن real_check_date

        $datePart = $checkoutRecord->real_check_date ?? $checkoutRecord->check_date;
        $checkoutTime = Carbon::parse($datePart . ' ' . $checkoutRecord->check_time);

        // مقارنة: هل وقت الخروج >= وقت انتهاء الوردية
        return $checkoutTime->greaterThanOrEqualTo($shiftEnd);
    }

    protected function throwError(string $key, string $message): void
    {
        throw ValidationException::withMessages([
            $key => $message
        ]);
    }
}
