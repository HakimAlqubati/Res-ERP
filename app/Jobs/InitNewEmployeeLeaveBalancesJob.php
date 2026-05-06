<?php

namespace App\Jobs;

use App\Models\Employee;
use App\Modules\HR\Leaves\InitEmployeeLeaves\Init;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Job: InitNewEmployeeLeaveBalancesJob
 * وظيفة: تهيئة أرصدة الإجازة لموظف جديد
 *
 * تُطلَق تلقائياً من EmployeeObserver::created() عند إضافة موظف جديد.
 * تعمل في الخلفية عبر قائمة الانتظار (Queue) حتى لا تُعطّل المستخدم.
 * تحفظ الـ tenantId صراحةً وتستعيده يدوياً في handle() لضمان الاتصال بقاعدة البيانات الصحيحة.
 *
 * Automatically dispatched from EmployeeObserver::created() when a new employee is added.
 * Runs in the background via the queue so the user is not blocked.
 * Explicitly stores tenantId and restores it manually in handle() to ensure the correct DB connection.
 */
class InitNewEmployeeLeaveBalancesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * عدد محاولات إعادة التشغيل عند الفشل.
     * Number of times the job may be attempted on failure.
     */
    public int $tries = 3;

    /**
     * مهلة التنفيذ بالثواني.
     * Maximum execution time in seconds.
     */
    public int $timeout = 120;

    /**
     * @param int      $employeeId معرّف الموظف الجديد / The new employee's ID
     * @param int|null $tenantId   معرّف الـ Tenant الحالي / The current tenant's ID
     */
    public function __construct(
        public int $employeeId,
        public ?int $tenantId = null,
    ) {
        // إجبار الجوب على استخدام قاعدة بيانات الـ landlord كـ queue connection
        // Force the job to use the landlord database as the queue connection
        $this->onConnection('database');
    }

    /**
     * تنفيذ الوظيفة في الخلفية.
     * Execute the job in the background.
     */
    public function handle(): void
    {
        // ─── استعادة الـ Tenant يدوياً ────────────────────────────────────
        // Manually restore the tenant context before executing any DB queries
        if ($this->tenantId) {
            $tenant = \Spatie\Multitenancy\Models\Tenant::find($this->tenantId);
            if ($tenant) {
                $tenant->makeCurrent();
            }
        }

        // ─── التحقق من وجود الموظف ────────────────────────────────────────
        // Re-fetch the employee to confirm existence and load only the required columns
        $employee = Employee::query()
            ->select(['id', 'branch_id', 'has_employee_pass', 'join_date'])
            ->find($this->employeeId);

        if (!$employee) {
            // الموظف حُذف قبل تنفيذ الجوب — لا حاجة لإعادة المحاولة
            // Employee was deleted before the job ran — no need to retry
            Log::warning("InitNewEmployeeLeaveBalancesJob: Employee [{$this->employeeId}] not found. Skipping.");
            return;
        }

        // ─── تهيئة الأرصدة ────────────────────────────────────────────────
        // Initialize leave balances for the employee
        (new Init())->handleForNewEmployee($employee);

        Log::info("InitNewEmployeeLeaveBalancesJob: Leave balances initialized for employee [{$this->employeeId}] on tenant [{$this->tenantId}].");
    }

    /**
     * معالجة الفشل النهائي بعد استنفاد جميع المحاولات.
     * Handle the final failure after all retry attempts are exhausted.
     */
    public function failed(Throwable $exception): void
    {
        Log::error(
            "InitNewEmployeeLeaveBalancesJob: Failed for employee [{$this->employeeId}] on tenant [{$this->tenantId}].",
            ['error' => $exception->getMessage()]
        );
    }
}
