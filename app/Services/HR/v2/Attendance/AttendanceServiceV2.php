<?php

namespace App\Services\HR\v2\Attendance;

use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AttendanceServiceV2
{
    public function __construct(
        protected AttendanceHandlerV2 $handler
    ) {}

    public function handle(array $payload): array
    {
        // 1. Identify Employee
        $employee = $this->resolveEmployee($payload);
        if (!$employee) {
            return [
                'success' => false,
                'message' => 'Employee not found.',
            ];
        }

        // 2. Atomic Lock to prevent race conditions
        // Lock key based on employee ID
        $lockKey = 'attendance_lock_' . $employee->id;

        // Try to acquire lock for 5 seconds, wait up to 10 seconds
        return Cache::lock($lockKey, 10)->block(5, function () use ($employee, $payload) {
            return $this->processAttendance($employee, $payload);
        });
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
            Log::error('Attendance V2 Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $payload
            ]);

            return [
                'success' => false,
                'message' => 'System Error: ' . $e->getMessage(),
            ];
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
