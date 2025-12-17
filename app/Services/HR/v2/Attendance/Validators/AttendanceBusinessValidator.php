<?php

namespace App\Services\HR\v2\Attendance\Validators;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Setting;
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
     * @throws TypeRequiredException
     */
    public function validate(Employee $employee, Carbon $requestTime, ?string $requestType = null): void
    {
        $date = $requestTime->toDateString();

        // 0. تحديد الوردية الحالية المناسبة للوقت المطلوب
        $currentShift = $this->shiftResolver->resolve($employee, $requestTime);
        $currentPeriodId = $currentShift['period']->id ?? null;

        // 1. جلب السجلات لليوم الحالي
        $dailyRecords = Attendance::with('period')
            ->where('employee_id', $employee->id)
            ->where('check_date', $date)
            ->where('accepted', 1)
            ->get();

        // 2. فلترة السجلات حسب الوردية الحالية (إذا وجدت)
        // هذا يسمح بالتعامل مع ورديات متعددة في نفس اليوم
        if ($currentPeriodId) {
            $shiftRecords = $dailyRecords->where('period_id', $currentPeriodId);
        } else {
            // إذا لم نجد وردية محددة، نستخدم كل السجلات (التصرف القديم)
            $shiftRecords = $dailyRecords;
        }

        $checkInRecord = $shiftRecords->firstWhere('check_type', Attendance::CHECKTYPE_CHECKIN);
        $checkOutRecord = $shiftRecords
            ->sortByDesc('check_time')
            ->firstWhere('check_type', Attendance::CHECKTYPE_CHECKOUT);

        // --- القواعد (Business Rules) ---

        // القاعدة 1: الوردية الحالية مكتملة (دخول + خروج) -> هل نغلق الباب؟
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

        // القاعدة 4: طلب قرب نهاية الشيفت بدون سجل دخول - يتطلب تحديد النوع صراحة
        // هذه القاعدة تمنع تسجيل دخول تلقائي في نهاية الدوام بدون سجلات سابقة
        if (!$checkInRecord && $requestType === null) {
            $this->validateNearShiftEndWithoutRecords($employee, $requestTime);
        }
    }

    /**
     * التحقق من الطلبات القريبة من نهاية الشيفت بدون سجلات
     * 
     * @throws TypeRequiredException
     */
    protected function validateNearShiftEndWithoutRecords(Employee $employee, Carbon $requestTime): void
    {
        // جلب الوردية المحتملة لهذا الوقت
        $shiftInfo = $this->shiftResolver->resolve($employee, $requestTime);

        if (!$shiftInfo || !isset($shiftInfo['bounds'])) {
            return; // لا توجد وردية، سيتم التعامل معها في مكان آخر
        }

        $bounds = $shiftInfo['bounds'];

        // استخدام الإعداد الموجود (بالساعات) وتحويله لدقائق
        $hoursBeforePeriod = (int) Setting::getSetting('pre_end_hours_for_check_in_out', 1);
        $thresholdMinutes = $hoursBeforePeriod * 60;

        // حساب وقت العتبة (نهاية الشيفت - threshold دقيقة)
        $thresholdTime = $bounds['end']->copy()->subMinutes($thresholdMinutes);

        // إذا كان الوقت الحالي بعد العتبة (قريب من نهاية أو بعد نهاية الشيفت)
        if ($requestTime->gte($thresholdTime)) {
            throw new TypeRequiredException(
                __('notifications.cannot_auto_checkin_near_shift_end')
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
