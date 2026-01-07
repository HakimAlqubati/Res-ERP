<?php

namespace App\Modules\HR\Attendance\Services;

use App\Models\Employee;
use App\Models\WorkPeriod;
use App\Modules\HR\Attendance\Enums\AttendanceType;
use App\Modules\HR\Attendance\Enums\CheckType;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * خدمة توليد سجلات الحضور بشكل جماعي (Module Version)
 * 
 * تقوم هذه الخدمة بتوليد سجلات حضور وانصراف لموظف معين
 * خلال فترة زمنية محددة مع أوقات عشوائية واقعية
 * 
 * تستخدم هذه الخدمة AttendanceService الجديدة لإنشاء السجلات
 */
class BulkAttendanceGeneratorService
{
    /**
     * الهامش الزمني الأقصى بالدقائق (قبل أو بعد الوقت الرسمي)
     */
    private const VARIANCE_MINUTES = 30;

    public function __construct(
        private AttendanceService $attendanceService
    ) {}

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

        // 2. جلب البيانات
        $employee = Employee::find($payload['employee_id']);
        $workPeriod = WorkPeriod::find($payload['work_period_id']);

        if (!$employee || !$workPeriod) {
            return ['status' => false, 'message' => 'Employee or Work period not found.'];
        }

        // 3. تحويل التواريخ
        $fromDate = Carbon::parse($payload['from_date']);
        $toDate = Carbon::parse($payload['to_date']);

        if ($fromDate->greaterThan($toDate)) {
            return ['status' => false, 'message' => 'from_date must be before or equal to to_date.'];
        }

        // 4. التنفيذ داخل Transaction
        try {
            $results = DB::transaction(function () use ($employee, $workPeriod, $fromDate, $toDate) {
                return $this->generateAttendanceRecords($employee, $workPeriod, $fromDate, $toDate);
            });
        } catch (\Throwable $e) {
            return [
                'status' => false,
                'message' => 'Failed to generate attendance records: ' . $e->getMessage(),
            ];
        }

        // 5. إعداد الاستجابة
        $allFailed = $results['successful_checkins'] === 0 && $results['successful_checkouts'] === 0 && $results['days_processed'] > 0;

        return [
            'status' => !$allFailed,
            'message' => $allFailed ? 'All records failed.' : 'Generation completed.',
            'data' => [
                'summary' => [
                    'days_processed' => $results['days_processed'],
                    'successful_checkins' => $results['successful_checkins'],
                    'successful_checkouts' => $results['successful_checkouts'],
                    'failed_records' => $results['failed_records'],
                ],
                'details' => $results['details'],
                'failures_summary' => $this->buildFailuresSummary($results['failure_reasons']),
            ],
        ];
    }

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

    private function generateAttendanceRecords(Employee $employee, WorkPeriod $workPeriod, Carbon $fromDate, Carbon $toDate): array
    {
        $stats = [
            'days_processed' => 0,
            'successful_checkins' => 0,
            'successful_checkouts' => 0,
            'failed_records' => 0,
            'details' => [],
            'failure_reasons' => [],
        ];

        $workDays = $this->normalizeDays(is_string($workPeriod->days) ? json_decode($workPeriod->days, true) : $workPeriod->days);

        if (empty($workDays)) {
            return $stats;
        }

        $currentDate = $fromDate->copy();

        while ($currentDate->lessThanOrEqualTo($toDate)) {
            $dayName = $currentDate->format('l');

            // تسجيل الدخول
            $checkinTime = $this->generateRandomCheckinTime($currentDate, $workPeriod);
            $this->processRecord($employee, $checkinTime, CheckType::CHECKIN->value, $stats, $dayName);

            // تسجيل الخروج
            $checkoutTime = $this->generateRandomCheckoutTime($currentDate, $workPeriod);
            $this->processRecord($employee, $checkoutTime, CheckType::CHECKOUT->value, $stats, $dayName);

            $stats['days_processed']++;
            $currentDate->addDay();
        }

        return $stats;
    }

    private function processRecord(Employee $employee, Carbon $time, string $type, array &$stats, string $dayName): void
    {
        try {
            $payload = [
                'employee_id' => $employee->id,
                'date_time' => $time->format('Y-m-d H:i:s'),
                'type' => $type,
                'attendance_type' => AttendanceType::REQUEST->value,
            ];

            $result = $this->attendanceService->handle($payload);

            if ($result->success) {
                if ($type === CheckType::CHECKIN->value) {
                    $stats['successful_checkins']++;
                } else {
                    $stats['successful_checkouts']++;
                }

                $stats['details'][] = [
                    'date' => $time->toDateString(),
                    'type' => $type,
                    'status' => 'success'
                ];
            } else {
                $stats['failed_records']++;
                $this->collectFailureReason($stats['failure_reasons'], $type, $result->message);
            }
        } catch (\Throwable $e) {
            $stats['failed_records']++;
            $this->collectFailureReason($stats['failure_reasons'], $type, $e->getMessage());
        }
    }

    private function generateRandomCheckinTime(Carbon $date, WorkPeriod $workPeriod): Carbon
    {
        $officialStartTime = Carbon::parse($date->toDateString() . ' ' . $workPeriod->start_at);
        $variance = rand(-self::VARIANCE_MINUTES, self::VARIANCE_MINUTES);
        return $officialStartTime->addMinutes($variance);
    }

    private function generateRandomCheckoutTime(Carbon $date, WorkPeriod $workPeriod): Carbon
    {
        $officialEndTime = Carbon::parse($date->toDateString() . ' ' . $workPeriod->end_at);

        if ($workPeriod->day_and_night) {
            $officialEndTime->addDay();
        }

        $variance = rand(-self::VARIANCE_MINUTES, self::VARIANCE_MINUTES);
        return $officialEndTime->addMinutes($variance);
    }

    private function normalizeDays(?array $storedDays): array
    {
        // ... (Logic from V2) ...
        // For brevity using simplified version or copying V2 logic if needed strictly
        if (empty($storedDays)) return [];

        $dayMapping = [
            'sunday' => 'Sunday',
            'monday' => 'Monday',
            'tuesday' => 'Tuesday',
            'wednesday' => 'Wednesday',
            'thursday' => 'Thursday',
            'friday' => 'Friday',
            'saturday' => 'Saturday',
            'sun' => 'Sunday',
            'mon' => 'Monday',
            'tue' => 'Tuesday',
            'wed' => 'Wednesday',
            'thu' => 'Thursday',
            'fri' => 'Friday',
            'sat' => 'Saturday',
        ];

        $normalized = [];
        foreach ($storedDays as $day) {
            $lowerDay = strtolower(trim($day));
            $normalized[] = $dayMapping[$lowerDay] ?? ucfirst($lowerDay);
        }
        return $normalized;
    }

    private function collectFailureReason(array &$failureReasons, string $type, string $reason): void
    {
        $key = md5($type . ':' . $reason);
        if (!isset($failureReasons[$key])) {
            $failureReasons[$key] = [
                'type' => $type,
                'reason' => $reason,
                'count' => 0,
            ];
        }
        $failureReasons[$key]['count']++;
    }

    private function buildFailuresSummary(array $failureReasons): array
    {
        $summary = array_values($failureReasons);
        usort($summary, fn($a, $b) => $b['count'] <=> $a['count']);
        return $summary;
    }
}
