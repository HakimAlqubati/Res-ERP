<?php

namespace App\Services\HR\v2\Attendance;

use App\Models\Employee;
use App\Models\WorkPeriod;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * خدمة توليد سجلات الحضور بشكل جماعي
 * 
 * تقوم هذه الخدمة بتوليد سجلات حضور وانصراف لموظف معين
 * خلال فترة زمنية محددة مع أوقات عشوائية واقعية
 * 
 * تستخدم هذه الخدمة AttendanceServiceV2 داخلياً لإنشاء السجلات
 * بدلاً من إعادة كتابة منطق الإنشاء
 * 
 * ========== الإصلاحات المطبقة ==========
 * 1. إصلاح مشكلة حساسية حالة الأحرف (Case Sensitivity) في أسماء الأيام
 *    - Carbon::format('l') تُرجع 'Monday' بحرف كبير
 *    - أيام العمل المخزنة قد تكون بحروف صغيرة 'monday' أو بتنسيق مختلف
 *    - تم تطبيق تطبيع (Normalization) لضمان المطابقة الصحيحة
 * 
 * 2. إضافة DB::transaction لضمان سلامة البيانات (Atomicity)
 * 
 * 3. إضافة تسجيل أخطاء شامل (Logging) لتسهيل التتبع
 * 
 * 4. تصحيح منطق ملء مصفوفة details
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

        // ========== إصلاح: إضافة DB::transaction لضمان سلامة البيانات ==========
        // نقوم بتنفيذ التوليد داخل Transaction لضمان إما نجاح الكل أو فشل الكل
        try {
            $results = DB::transaction(function () use ($employee, $workPeriod, $fromDate, $toDate) {
                return $this->generateAttendanceRecords(
                    $employee,
                    $workPeriod,
                    $fromDate,
                    $toDate
                );
            });
        } catch (\Throwable $e) {
            // ========== إصلاح: تسجيل الاستثناءات للتتبع ==========
            Log::error('BulkAttendanceGenerator: Transaction failed', [
                'employee_id' => $employee->id,
                'work_period_id' => $workPeriod->id,
                'from_date' => $fromDate->toDateString(),
                'to_date' => $toDate->toDateString(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'status' => false,
                'message' => 'Failed to generate attendance records: ' . $e->getMessage(),
            ];
        }

        // ========== تحسين: تحديد حالة النجاح بناءً على النتائج ==========
        $hasFailures = $results['failed_records'] > 0;
        $allFailed = $results['successful_checkins'] === 0 && $results['successful_checkouts'] === 0 && $results['days_processed'] > 0;

        // تحديد رسالة الحالة بناءً على النتائج
        if ($allFailed) {
            $statusMessage = 'All attendance records failed to generate.';
        } elseif ($hasFailures) {
            $statusMessage = 'Attendance records generated with some failures.';
        } else {
            $statusMessage = 'Attendance records generated successfully.';
        }

        return [
            'status' => !$allFailed, // false فقط إذا فشل الكل
            'message' => $statusMessage,
            'data' => [
                'employee_id' => $employee->id,
                'employee_name' => $employee->name,
                'from_date' => $fromDate->toDateString(),
                'to_date' => $toDate->toDateString(),
                'work_period' => $workPeriod->name,
                'work_period_days' => $results['work_period_days'] ?? [],
                // ========== إحصائيات ==========
                'summary' => [
                    'days_processed' => $results['days_processed'],
                    'successful_checkins' => $results['successful_checkins'],
                    'successful_checkouts' => $results['successful_checkouts'],
                    'failed_records' => $results['failed_records'],
                    'success_rate' => $results['days_processed'] > 0
                        ? round((($results['successful_checkins'] + $results['successful_checkouts']) / ($results['days_processed'] * 2)) * 100, 2) . '%'
                        : '0%',
                ],
                // ========== تفاصيل السجلات ==========
                'details' => $results['details'],
                // ========== تفاصيل الفشل ==========
                'failures' => $results['failures'],
                // ========== ملخص أسباب الفشل ==========
                'failures_summary' => $results['failures_summary'],
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
     * ========== إصلاح: تطبيع أسماء الأيام لضمان المطابقة ==========
     * 
     * تقوم بتحويل أسماء الأيام المخزنة إلى شكل موحد للمقارنة
     * تدعم التنسيقات التالية:
     * - 'Monday', 'monday', 'MONDAY' (حالة الأحرف)
     * - 'mon', 'Mon' (اختصارات)
     * - 'monday', 'Monday' (أسماء كاملة)
     * 
     * @param array|null $storedDays الأيام المخزنة في قاعدة البيانات
     * @return array الأيام بعد التطبيع (كلها في صيغة 'Monday')
     */
    private function normalizeDays(?array $storedDays): array
    {
        if (empty($storedDays)) {
            return [];
        }

        // خريطة تحويل الاختصارات والأسماء الكاملة
        $dayMapping = [
            // الأسماء الكاملة (lowercase)
            'sunday' => 'Sunday',
            'monday' => 'Monday',
            'tuesday' => 'Tuesday',
            'wednesday' => 'Wednesday',
            'thursday' => 'Thursday',
            'friday' => 'Friday',
            'saturday' => 'Saturday',
            // الاختصارات (lowercase)
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
            if (isset($dayMapping[$lowerDay])) {
                $normalized[] = $dayMapping[$lowerDay];
            } else {
                // إذا لم يتم العثور على تطابق، نحاول تحويل الحرف الأول لكبير
                $normalized[] = ucfirst($lowerDay);
            }
        }

        return $normalized;
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

        // ========== تحسين: إضافة مصفوفات لتتبع الفشل ==========
        $failures = [];              // تفاصيل السجلات الفاشلة فقط
        $failureReasons = [];        // تجميع أسباب الفشل
        $skippedDaysDetails = [];    // تفاصيل الأيام المتخطاة

        // ========== إصلاح: معالجة مصفوفة أيام العمل بشكل صحيح ==========
        $workDays = $workPeriod->days;

        // التحقق من نوع البيانات وتحويلها إذا لزم الأمر
        if (is_string($workDays)) {
            $workDays = json_decode($workDays, true);
        }

        // ========== تسجيل للتتبع: أيام العمل الأصلية ==========
        Log::info('BulkAttendanceGenerator: Starting generation', [
            'employee_id' => $employee->id,
            'employee_name' => $employee->name,
            'work_period_id' => $workPeriod->id,
            'work_period_name' => $workPeriod->name,
            'from_date' => $fromDate->toDateString(),
            'to_date' => $toDate->toDateString(),
            'raw_work_days' => $workDays,
        ]);

        // ========== إصلاح: تطبيع أيام العمل ==========
        $normalizedWorkDays = $this->normalizeDays($workDays);

        Log::info('BulkAttendanceGenerator: Normalized work days', [
            'original' => $workDays,
            'normalized' => $normalizedWorkDays,
        ]);

        // ========== إصلاح: التحقق من وجود أيام عمل ==========
        if (empty($normalizedWorkDays)) {
            Log::warning('BulkAttendanceGenerator: No work days defined for period', [
                'work_period_id' => $workPeriod->id,
                'work_period_name' => $workPeriod->name,
            ]);

            return [
                'days_processed' => 0,
                'successful_checkins' => 0,
                'successful_checkouts' => 0,
                'failed_records' => 0,
                'skipped_days' => 0,
                'details' => [],
                'warning' => 'No work days defined for this work period.',
            ];
        }

        // حلقة تغطي جميع الأيام في النطاق المحدد
        $currentDate = $fromDate->copy();
        while ($currentDate->lessThanOrEqualTo($toDate)) {
            $dayName = $currentDate->format('l'); // اسم اليوم (Monday, Tuesday, etc.)

            // ========== تحسين: معالجة كل يوم وترك AttendanceServiceV2 يحدد الصلاحية ==========
            $dayResult = [
                'date' => $currentDate->toDateString(),
                'day' => $dayName,
                'checkin' => null,
                'checkout' => null,
            ];

            // ========== معالجة تسجيل الحضور (Check-in) ==========
            try {
                // توليد وقت الحضور العشوائي
                $checkinTime = $this->generateRandomCheckinTime($currentDate, $workPeriod);

                // استدعاء AttendanceServiceV2 لإنشاء سجل الحضور
                $checkinPayload = [
                    'employee_id' => $employee->id,
                    'date_time' => $checkinTime->format('Y-m-d H:i:s'),
                    'type' => 'checkin',
                    'attendance_type' => 'request',
                ];

                $checkinResult = $this->attendanceService->handle($checkinPayload);

                // ========== النتيجة الكاملة من AttendanceServiceV2 ==========
                $dayResult['checkin'] = [
                    'time' => $checkinTime->toTimeString(),
                    'payload' => $checkinPayload,
                    'result' => $checkinResult,
                ];

                if ($checkinResult['status']) {
                    $successfulCheckins++;
                } else {
                    $failedRecords++;
                    $failureReason = $checkinResult['message'] ?? 'Unknown error';
                    $this->collectFailureReason($failureReasons, 'checkin', $failureReason);
                    $failures[] = [
                        'date' => $currentDate->toDateString(),
                        'day' => $dayName,
                        'type' => 'checkin',
                        'time' => $checkinTime->toTimeString(),
                        'reason' => $failureReason,
                    ];
                }
            } catch (\Throwable $e) {
                $failedRecords++;
                $exceptionMessage = 'Exception: ' . $e->getMessage();
                $dayResult['checkin'] = [
                    'time' => null,
                    'payload' => $checkinPayload ?? null,
                    'result' => ['status' => false, 'message' => $exceptionMessage],
                ];
                $this->collectFailureReason($failureReasons, 'checkin', $exceptionMessage);
                $failures[] = [
                    'date' => $currentDate->toDateString(),
                    'day' => $dayName,
                    'type' => 'checkin',
                    'time' => null,
                    'reason' => $exceptionMessage,
                ];
                Log::error('BulkAttendanceGenerator: Checkin exception', [
                    'employee_id' => $employee->id,
                    'date' => $currentDate->toDateString(),
                    'error' => $e->getMessage(),
                ]);
            }

            // ========== معالجة تسجيل الانصراف (Check-out) ==========
            try {
                // توليد وقت الانصراف العشوائي
                $checkoutTime = $this->generateRandomCheckoutTime($currentDate, $workPeriod);

                // استدعاء AttendanceServiceV2 لإنشاء سجل الانصراف
                $checkoutPayload = [
                    'employee_id' => $employee->id,
                    'date_time' => $checkoutTime->format('Y-m-d H:i:s'),
                    'type' => 'checkout',
                    'attendance_type' => 'request',
                ];

                $checkoutResult = $this->attendanceService->handle($checkoutPayload);

                // ========== النتيجة الكاملة من AttendanceServiceV2 ==========
                $dayResult['checkout'] = [
                    'time' => $checkoutTime->toTimeString(),
                    'payload' => $checkoutPayload,
                    'result' => $checkoutResult,
                ];

                if ($checkoutResult['status']) {
                    $successfulCheckouts++;
                } else {
                    $failedRecords++;
                    $failureReason = $checkoutResult['message'] ?? 'Unknown error';
                    $this->collectFailureReason($failureReasons, 'checkout', $failureReason);
                    $failures[] = [
                        'date' => $currentDate->toDateString(),
                        'day' => $dayName,
                        'type' => 'checkout',
                        'time' => $checkoutTime->toTimeString(),
                        'reason' => $failureReason,
                    ];
                }
            } catch (\Throwable $e) {
                $failedRecords++;
                $exceptionMessage = 'Exception: ' . $e->getMessage();
                $dayResult['checkout'] = [
                    'time' => null,
                    'payload' => $checkoutPayload ?? null,
                    'result' => ['status' => false, 'message' => $exceptionMessage],
                ];
                $this->collectFailureReason($failureReasons, 'checkout', $exceptionMessage);
                $failures[] = [
                    'date' => $currentDate->toDateString(),
                    'day' => $dayName,
                    'type' => 'checkout',
                    'time' => null,
                    'reason' => $exceptionMessage,
                ];
                Log::error('BulkAttendanceGenerator: Checkout exception', [
                    'employee_id' => $employee->id,
                    'date' => $currentDate->toDateString(),
                    'error' => $e->getMessage(),
                ]);
            }

            // إضافة نتيجة اليوم إلى details
            $details[] = $dayResult;
            $daysProcessed++;

            $currentDate->addDay();
        }

        // ========== تسجيل ملخص العملية ==========
        Log::info('BulkAttendanceGenerator: Generation completed', [
            'employee_id' => $employee->id,
            'days_processed' => $daysProcessed,
            'successful_checkins' => $successfulCheckins,
            'successful_checkouts' => $successfulCheckouts,
            'failed_records' => $failedRecords,
            'details_count' => count($details),
        ]);

        // ========== تحسين: بناء ملخص أسباب الفشل ==========
        $failuresSummary = $this->buildFailuresSummary($failureReasons);

        return [
            'days_processed' => $daysProcessed,
            'successful_checkins' => $successfulCheckins,
            'successful_checkouts' => $successfulCheckouts,
            'failed_records' => $failedRecords,
            'work_period_days' => $normalizedWorkDays,
            'details' => $details,
            'failures' => $failures,
            'failures_summary' => $failuresSummary,
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

    /**
     * ========== تجميع أسباب الفشل ==========
     * 
     * تقوم بتجميع أسباب الفشل وتصنيفها حسب النوع (checkin/checkout)
     * يساعد في إنشاء ملخص واضح لأسباب عدم الحفظ
     * 
     * @param array &$failureReasons المصفوفة المرجعية لتخزين الأسباب
     * @param string $type نوع العملية (checkin/checkout)
     * @param string $reason سبب الفشل
     */
    private function collectFailureReason(array &$failureReasons, string $type, string $reason): void
    {
        // إنشاء مفتاح فريد للسبب
        $key = md5($type . ':' . $reason);

        if (!isset($failureReasons[$key])) {
            $failureReasons[$key] = [
                'type' => $type,
                'reason' => $reason,
                'count' => 0,
                'dates' => [],
            ];
        }

        $failureReasons[$key]['count']++;
    }

    /**
     * ========== بناء ملخص أسباب الفشل ==========
     * 
     * تحويل المصفوفة المجمعة إلى ملخص واضح ومرتب
     * يظهر كل سبب مع عدد التكرارات والنوع
     * 
     * مثال الخرج:
     * [
     *   [
     *     'type' => 'checkin',
     *     'reason' => 'Attendance for this date is already completed.',
     *     'count' => 5,
     *     'percentage' => '50%'
     *   ],
     *   ...
     * ]
     * 
     * @param array $failureReasons المصفوفة المجمعة
     * @return array ملخص مرتب ومنظم
     */
    private function buildFailuresSummary(array $failureReasons): array
    {
        if (empty($failureReasons)) {
            return [];
        }

        $totalFailures = array_sum(array_column($failureReasons, 'count'));

        $summary = [];
        foreach ($failureReasons as $failure) {
            $summary[] = [
                'type' => $failure['type'],
                'type_label' => $failure['type'] === 'checkin' ? 'تسجيل حضور' : 'تسجيل انصراف',
                'reason' => $failure['reason'],
                'count' => $failure['count'],
                'percentage' => $totalFailures > 0
                    ? round(($failure['count'] / $totalFailures) * 100, 1) . '%'
                    : '0%',
            ];
        }

        // ترتيب حسب العدد (الأكثر تكراراً أولاً)
        usort($summary, function ($a, $b) {
            return $b['count'] <=> $a['count'];
        });

        return $summary;
    }
}
