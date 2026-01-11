<?php

namespace App\Modules\HR\Attendance\Services;

use App\Models\Employee;
use App\Modules\HR\Attendance\Actions\ResolveEmployeeAction;
use App\Modules\HR\Attendance\DTOs\AttendanceContextDTO;
use App\Modules\HR\Attendance\DTOs\AttendanceResultDTO;
use App\Modules\HR\Attendance\Events\AttendanceRejected;
use App\Modules\HR\Attendance\Exceptions\AttendanceException;
use App\Modules\HR\Attendance\Exceptions\TypeRequiredException;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
 
/**
 * خدمة الحضور الرئيسية
 * 
 * نقطة الدخول الموحدة لجميع عمليات تسجيل الحضور
 * تنسق بين جميع المكونات الأخرى
 */
class AttendanceService
{
    public function __construct(
        private ResolveEmployeeAction $resolveEmployee,
        private AttendanceValidator $validator,
        private AttendanceHandler $handler,
        private AttendanceConfig $config,
    ) {}

    /**
     * معالجة طلب تسجيل الحضور
     */
    public function handle(array $payload): AttendanceResultDTO
    {
        // 1. تحديد الموظف
        $employee = $this->resolveEmployee->execute($payload);

        if (!$employee) {
            return AttendanceResultDTO::failure(__('Employee not found.'));
        }

        // 2. تحليل الوقت
        $requestTime = $this->parseRequestTime($payload);

        if ($requestTime === null) {
            return AttendanceResultDTO::failure(__('Invalid date format.'));
        }

        // 3. التحقق من القواعد
        $validationResult = $this->validateRequest($employee, $requestTime, $payload);

        if ($validationResult !== null) {
            return $validationResult;
        }

        // 4. تنفيذ العملية مع Lock
        return $this->executeWithLock($employee, $payload, $requestTime);
    }

    /**
     * تحليل وقت الطلب
     */
    private function parseRequestTime(array $payload): ?Carbon
    {
        try {
            return isset($payload['date_time'])
                ? Carbon::parse($payload['date_time'])
                : Carbon::now();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * التحقق من قواعد العمل
     */
    private function validateRequest(Employee $employee, Carbon $requestTime, array $payload): ?AttendanceResultDTO
    {
        try {
            $this->validator->validate($employee, $requestTime, $payload['type'] ?? null);
            return null; // التحقق نجح
        } catch (TypeRequiredException $e) {
            $this->handleRejection($employee, $requestTime, $e->getMessage(), $payload);
            return AttendanceResultDTO::failure($e->getMessage(), typeRequired: true);
        } catch (AttendanceException $e) {
            $this->handleRejection($employee, $requestTime, $e->getMessage(), $payload);
            return AttendanceResultDTO::failure($e->getMessage());
        } catch (\Throwable $e) {
            $this->handleRejection($employee, $requestTime, $e->getMessage(), $payload);
            return AttendanceResultDTO::failure($e->getMessage());
        }
    }

    /**
     * معالجة رفض تسجيل الحضور
     */
    private function handleRejection(Employee $employee, Carbon $requestTime, string $message, array $payload): void
    {
        // إطلاق حدث الرفض (يتم التعامل معه عبر Listeners)
        AttendanceRejected::dispatch($employee, $message, $requestTime, $payload);
    }

    /**
     * تنفيذ العملية مع Lock لمنع التزامن
     */
    private function executeWithLock(Employee $employee, array $payload, Carbon $requestTime): AttendanceResultDTO
    {
        $lockKey = 'attendance_lock_' . $employee->id;

        try {
            return Cache::lock($lockKey, $this->config->getLockTimeout())
                ->block($this->config->getLockWaitTime(), function () use ($employee, $payload) {
                    return $this->processAttendance($employee, $payload);
                });
        } catch (\Throwable $e) {
        

            $this->handleRejection(
                $employee,
                $requestTime,
                'Error: ' . $e->getMessage(),
                $payload
            );

            // In development, show the real error
            $message = config('app.debug')
                ? 'Error: ' . $e->getMessage()
                : __('System busy, please try again.');

            return AttendanceResultDTO::failure($message);
        }
    }

    /**
     * معالجة الحضور داخل Transaction
     */
    private function processAttendance(Employee $employee, array $payload): AttendanceResultDTO
    {
        return DB::transaction(function () use ($employee, $payload) {
            $context = AttendanceContextDTO::fromPayload($employee, $payload);
            return $this->handler->handle($context);
        });
    }
}
