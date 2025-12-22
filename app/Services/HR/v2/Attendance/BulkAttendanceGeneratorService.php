<?php

namespace App\Services\HR\v2\Attendance;

use App\Models\Employee;
use App\Models\WorkPeriod;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

/**
 * خدمة توليد سجلات الحضور بشكل جماعي
 * 
 * تقوم هذه الخدمة بتوليد سجلات حضور وانصراف لموظف معين
 * خلال فترة زمنية محددة مع أوقات عشوائية واقعية
 * 
 * تستخدم هذه الخدمة AttendanceServiceV2 داخلياً لإنشاء السجلات
 * بدلاً من إعادة كتابة منطق الإنشاء
 */
class BulkAttendanceGeneratorService
{
    /**
     * الهامش الزمني الأقصى بالدقائق (قبل أو بعد الوقت الرسمي)
     * يمكن أن يحضر الموظف مبكراً أو متأخراً بحد أقصى 30 دقيقة
     */
    private const VARIANCE_MINUTES = 30;

    /**
     * خدمة الحضور الموجودة
     */
    protected AttendanceServiceV2 $attendanceService;

    public function __construct(AttendanceServiceV2 $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    /**
     * توليد سجلات الحضور للموظف خلال الفترة المحددة
     *
     * @param array $payload البيانات المدخلة
     * @return array نتيجة العملية
     */
    public function generate(array $payload): array
    {
        // 1. التحقق من صحة البيانات المدخلة
        $validation = $this->validateInput($payload);
        if ($validation['status'] === false) {
            return $validation;
        }

        // 2. جلب بيانات الموظف وفترة العمل
        $employee = Employee::find($payload['employee_id']);
        if (!$employee) {
            return [
                'status' => false,
                'message' => 'Employee not found.',
            ];
        }

        $workPeriod = WorkPeriod::find($payload['work_period_id']);
        if (!$workPeriod) {
            return [
                'status' => false,
                'message' => 'Work period not found.',
            ];
        }

        // 3. تحويل التواريخ إلى كائنات Carbon
        $fromDate = Carbon::parse($payload['from_date']);
        $toDate = Carbon::parse($payload['to_date']);

        // 4. التحقق من صحة نطاق التواريخ
        if ($fromDate->greaterThan($toDate)) {
            return [
                'status' => false,
                'message' => 'from_date must be before or equal to to_date.',
            ];
        }

        // 5. بدء عملية التوليد
        $results = $this->generateAttendanceRecords(
            $employee,
            $workPeriod,
            $fromDate,
            $toDate
        );

        return [
            'status' => true,
            'message' => 'Attendance records generated successfully.',
            'data' => [
                'employee_id' => $employee->id,
                'employee_name' => $employee->name,
                'from_date' => $fromDate->toDateString(),
                'to_date' => $toDate->toDateString(),
                'work_period' => $workPeriod->name,
                'days_processed' => $results['days_processed'],
                'successful_checkins' => $results['successful_checkins'],
                'successful_checkouts' => $results['successful_checkouts'],
                'failed_records' => $results['failed_records'],
                'details' => $results['details'],
            ],
        ];
    }

    /**
     * التحقق من صحة البيانات المدخلة
     */
    private function validateInput(array $payload): array
    {
        $validator = Validator::make($payload, [
            'employee_id' => 'required|integer|exists:hr_employees,id',
            'from_date' => 'required|date|date_format:Y-m-d',
            'to_date' => 'required|date|date_format:Y-m-d',
            'work_period_id' => 'required|integer|exists:hr_work_periods,id',
        ]);

        if ($validator->fails()) {
            return [
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()->toArray(),
            ];
        }

        return ['status' => true];
    }

    /**
     * توليد سجلات الحضور لكل يوم في النطاق المحدد
     * باستخدام AttendanceServiceV2 الموجود
     */
    private function generateAttendanceRecords(
        Employee $employee,
        WorkPeriod $workPeriod,
        Carbon $fromDate,
        Carbon $toDate
    ): array {
        $daysProcessed = 0;
        $successfulCheckins = 0;
        $successfulCheckouts = 0;
        $failedRecords = 0;
        $details = [];

        // حلقة تغطي جميع الأيام في النطاق المحدد
        $currentDate = $fromDate->copy();
        while ($currentDate->lessThanOrEqualTo($toDate)) {
            $dayName = $currentDate->format('l'); // اسم اليوم (Saturday, Sunday, etc.)

            // التحقق مما إذا كان هذا اليوم ضمن أيام العمل
            $workDays = $workPeriod->days;
            if (is_string($workDays)) {
                $workDays = json_decode($workDays, true);
            }

            // إذا كان اليوم ضمن أيام العمل المحددة في فترة العمل
            if ($workDays && in_array($dayName, $workDays)) {
                $dayResult = [
                    'date' => $currentDate->toDateString(),
                    'day' => $dayName,
                ];

                // توليد وقت الحضور العشوائي (Check-in)
                $checkinTime = $this->generateRandomCheckinTime($currentDate, $workPeriod);

                // استدعاء AttendanceServiceV2 لإنشاء سجل الحضور
                $checkinPayload = [
                    'employee_id' => $employee->id,
                    'date_time' => $checkinTime->format('Y-m-d H:i:s'),
                    'type' => 'checkin',
                    'attendance_type' => 'request',
                ];

                $checkinResult = $this->attendanceService->handle($checkinPayload);
                $dayResult['checkin'] = [
                    'time' => $checkinTime->toTimeString(),
                    'status' => $checkinResult['status'],
                    'message' => $checkinResult['message'] ?? null,
                ];

                if ($checkinResult['status']) {
                    $successfulCheckins++;
                } else {
                    $failedRecords++;
                }

                // توليد وقت الانصراف العشوائي (Check-out)
                $checkoutTime = $this->generateRandomCheckoutTime($currentDate, $workPeriod);

                // استدعاء AttendanceServiceV2 لإنشاء سجل الانصراف
                $checkoutPayload = [
                    'employee_id' => $employee->id,
                    'date_time' => $checkoutTime->format('Y-m-d H:i:s'),
                    'type' => 'checkout',
                    'attendance_type' => 'request',
                ];

                $checkoutResult = $this->attendanceService->handle($checkoutPayload);
                $dayResult['checkout'] = [
                    'time' => $checkoutTime->toTimeString(),
                    'status' => $checkoutResult['status'],
                    'message' => $checkoutResult['message'] ?? null,
                ];

                if ($checkoutResult['status']) {
                    $successfulCheckouts++;
                } else {
                    $failedRecords++;
                }

                $details[] = $dayResult;
                $daysProcessed++;
            }

            $currentDate->addDay();
        }

        return [
            'days_processed' => $daysProcessed,
            'successful_checkins' => $successfulCheckins,
            'successful_checkouts' => $successfulCheckouts,
            'failed_records' => $failedRecords,
            'details' => $details,
        ];
    }

    /**
     * توليد وقت الحضور العشوائي
     * 
     * منطق العشوائية:
     * - الوقت الأساسي ± (0 إلى VARIANCE_MINUTES) دقيقة
     * - القيمة السالبة تعني حضور مبكر
     * - القيمة الموجبة تعني تأخر
     * 
     * مثال: إذا كان الدوام يبدأ 08:00
     * - قد يحضر 07:35 (مبكر 25 دقيقة)
     * - قد يحضر 08:00 (في الموعد)
     * - قد يحضر 08:15 (متأخر 15 دقيقة)
     *
     * @param Carbon $date تاريخ اليوم
     * @param WorkPeriod $workPeriod فترة العمل
     * @return Carbon الوقت المعدل عشوائياً
     */
    private function generateRandomCheckinTime(Carbon $date, WorkPeriod $workPeriod): Carbon
    {
        // تحويل وقت بداية الدوام إلى كائن Carbon
        $officialStartTime = Carbon::parse($date->toDateString() . ' ' . $workPeriod->start_at);

        // توليد رقم عشوائي بين -30 و +30 دقيقة
        $variance = rand(-self::VARIANCE_MINUTES, self::VARIANCE_MINUTES);

        return $officialStartTime->addMinutes($variance);
    }

    /**
     * توليد وقت الانصراف العشوائي
     * 
     * منطق العشوائية:
     * - الوقت الأساسي ± (0 إلى VARIANCE_MINUTES) دقيقة
     * - يأخذ بالاعتبار الوردية الليلية
     * 
     * مثال: إذا كان الدوام ينتهي 17:00
     * - قد ينصرف 16:40 (مبكر 20 دقيقة)
     * - قد ينصرف 17:00 (في الموعد)
     * - قد ينصرف 17:20 (متأخر/عمل إضافي)
     *
     * @param Carbon $date تاريخ اليوم
     * @param WorkPeriod $workPeriod فترة العمل
     * @return Carbon الوقت المعدل عشوائياً
     */
    private function generateRandomCheckoutTime(Carbon $date, WorkPeriod $workPeriod): Carbon
    {
        // تحويل وقت نهاية الدوام إلى كائن Carbon
        $officialEndTime = Carbon::parse($date->toDateString() . ' ' . $workPeriod->end_at);

        // معالجة الوردية الليلية (إذا كان وقت الانصراف بعد منتصف الليل)
        if ($workPeriod->day_and_night) {
            $officialEndTime->addDay();
        }

        // توليد رقم عشوائي بين -30 و +30 دقيقة
        $variance = rand(-self::VARIANCE_MINUTES, self::VARIANCE_MINUTES);

        return $officialEndTime->addMinutes($variance);
    }
}
