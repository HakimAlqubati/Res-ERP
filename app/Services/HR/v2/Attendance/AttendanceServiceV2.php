<?php

namespace App\Services\HR\v2\Attendance;

use App\Models\Attendance;
use App\Models\Employee;
use App\Services\HR\v2\Attendance\Validators\AttendanceBusinessValidator;
use App\Services\HR\v2\Attendance\Validators\TypeRequiredException;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AttendanceServiceV2
{
    public function __construct(
        protected AttendanceHandlerV2 $handler,
        protected AttendanceBusinessValidator $validator // حقن الفالديشن هنا
    ) {}

    public function handle(array $payload): array
    {
        // 1. Identify Employee
        $employee = $this->resolveEmployee($payload);
        if (!$employee) {
            return [
                'status' => false,
                'message' => 'Employee not found.',
            ];
        }

        // 2. Pre-Validation Check (قبل الدخول في الـ Lock حتى)
        // هذا يوفر موارد السيرفر إذا كان الطلب مرفوضاً مسبقاً
        // لكن نحتاج للتاريخ والنوع من الـ Payload

        // معالجة التاريخ مع التحقق من صحة التنسيق
        try {
            $requestTime = isset($payload['date_time'])
                ? Carbon::parse($payload['date_time'])
                : Carbon::now();
        } catch (\Carbon\Exceptions\InvalidFormatException $e) {
            return [
                'status' => false,
                'message' => 'Invalid date format. Please use a valid date format like: Y-m-d H:i:s',
            ];
        }

        $type = $payload['type'] ?? null; // قد يكون null ويتم استنتاجه لاحقاً، لكن لو تم ارساله نفحصه

        // تشغيل الفالديشن المخصص
        // سيقوم برمي Exception ويوقف الكود تلقائياً إذا فشل
        try {
            $this->validator->validate($employee, $requestTime, $type);
        } catch (TypeRequiredException $e) {
            // حالة خاصة: طلب قرب نهاية الشيفت بدون سجلات - يتطلب تحديد النوع
            $this->storeRejectedAttendance($employee, $requestTime, $e->getMessage(), $payload);

            return [
                'status' => false,
                'message' => $e->getMessage(),
                'type_required' => true,
            ];
        } catch (\Throwable $e) {
            // Store rejected attendance record
            $this->storeRejectedAttendance($employee, $requestTime, $e->getMessage(), $payload);

            return [
                'status' => false,
                'message' => $e->getMessage(),
            ];
        }

        // 3. Atomic Lock to prevent race conditions
        // Lock key based on employee ID
        $lockKey = 'attendance_lock_' . $employee->id;

        // Try to acquire lock for 5 seconds, wait up to 10 seconds
        try {
            return Cache::lock($lockKey, 10)->block(5, function () use ($employee, $payload) {
                return $this->processAttendance($employee, $payload);
            });
        } catch (\Throwable $e) {
            // Store rejected attendance if lock fails
            $requestTime = isset($payload['date_time']) ? Carbon::parse($payload['date_time']) : Carbon::now();
            $this->storeRejectedAttendance($employee, $requestTime, 'Failed to acquire lock: ' . $e->getMessage(), $payload);

            return [
                'status' => false,
                'message' => 'System busy, please try again.',
            ];
        }
    }

    protected function processAttendance(Employee $employee, array $payload): array
    {
        DB::beginTransaction();
        try {
            // 3. Create Context
            $requestTime = isset($payload['date_time'])
                ? Carbon::parse($payload['date_time'])
                : Carbon::now();

            $ctx = new AttendanceContext(
                employee: $employee,
                requestTime: $requestTime,
                payload: $payload,
                attendanceType: $payload['attendance_type'] ?? 'rfid'
            );

            // 4. Delegate to Handler
            $result = $this->handler->handle($ctx);

            DB::commit();
            return $result;
        } catch (\Throwable $e) {
            DB::rollBack();

            // Store rejected attendance record
            $requestTime = isset($payload['date_time']) ? Carbon::parse($payload['date_time']) : Carbon::now();
            $this->storeRejectedAttendance($employee, $requestTime, 'System Error: ' . $e->getMessage(), $payload);

            Log::error('Attendance V2 Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $payload
            ]);

            return [
                'status' => false,
                'message' => 'System Error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Store a rejected attendance record with accepted = 0
     */
    protected function storeRejectedAttendance(Employee $employee, Carbon $requestTime, string $message, array $payload): void
    {
        try {
            $date = $requestTime->toDateString();
            $time = $requestTime->toTimeString();
            $day = $requestTime->format('l');
            $attendanceType = $payload['attendance_type'] ?? 'rfid';

            // Try to get period for this day
            $period = $employee->periods()
                ->whereJsonContains('days', $day)
                ->first();

            // If no period found for this day, use any period from employee
            if (!$period) {
                $period = $employee->periods()->first();
            }

            // If still no period, we cannot create the record
            if (!$period) {
                Log::warning('Cannot store rejected attendance - employee has no periods', [
                    'employee_id' => $employee->id,
                    'message' => $message
                ]);
                return;
            }

            Attendance::storeNotAccepted(
                $employee,
                $date,
                $time,
                $day,
                $message,
                $period->id,
                $attendanceType
            );
        } catch (\Throwable $e) {
            // If storing rejected record fails, just log it
            Log::error('Failed to store rejected attendance', [
                'error' => $e->getMessage(),
                'employee_id' => $employee->id,
                'message' => $message
            ]);
        }
    }

    protected function resolveEmployee(array $payload): ?Employee
    {
        if (isset($payload['employee']) && $payload['employee'] instanceof Employee) {
            return $payload['employee'];
        }
        if (isset($payload['employee_id'])) {
            return Employee::find($payload['employee_id']);
        }
        if (isset($payload['rfid'])) {
            return Employee::where('rfid', $payload['rfid'])->first();
        }
        return null;
    }
}
