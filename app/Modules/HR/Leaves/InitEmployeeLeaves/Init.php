<?php

namespace App\Modules\HR\Leaves\InitEmployeeLeaves;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Class Init
 * 
 * Coordinator class responsible for initializing the leave balances 
 * for all active employees. Optimized for high performance and low memory footprint.
 * 
 * @package App\Modules\HR\Leaves\InitEmployeeLeaves
 */
class Init
{
    /**
     * The chunk size for processing employees to prevent memory exhaustion.
     */
    private const CHUNK_SIZE = 500;

    /**
     * Execute the initialization process.
     *
     * @return void
     * @throws \Throwable
     */
    public function handle(): void
    {
        $now          = Carbon::now();
        $currentYear  = $now->year;
        $currentMonth = $now->month;

        DB::transaction(function () use ($currentYear, $currentMonth) {
            $this->clearExistingBalances($currentYear, $currentMonth);

            $leaveTypes = $this->getPreparedLeaveTypes();

            $this->processEmployeesInChunks($leaveTypes, $currentYear, $currentMonth);
        });

        Log::info('Leave balances initialization completed successfully.');
    }

    /**
     * Initialize leave balances for a single newly-created employee.
     * تهيئة أرصدة الإجازة لموظف جديد تم إضافته للتو.
     *
     * تُستخدم هذه الدالة من داخل InitNewEmployeeLeaveBalancesJob،
     * وتعيد استخدام نفس الخدمات الموجودة دون المساس بمنطق التهيئة الجماعية.
     *
     * This method is called from InitNewEmployeeLeaveBalancesJob.
     * It reuses all existing private services without affecting the bulk initialization logic.
     *
     * @param  Employee $employee الموظف الجديد (يجب أن يحتوي على: id, branch_id, has_employee_pass, join_date)
     * @return void
     * @throws \Throwable
     */
    public function handleForNewEmployee(Employee $employee): void
    {
        $now          = Carbon::now();
        $currentYear  = $now->year;
        $currentMonth = $now->month;

        DB::transaction(function () use ($employee, $currentYear, $currentMonth) {

            // جلب أنواع الإجازات النشطة مع الفروع المرتبطة بها
            // Fetch active leave types with their associated branches (eager-loaded)
            $leaveTypes = $this->getPreparedLeaveTypes();

            // بناء خريطة الاستهلاك لهذا الموظف فقط (query واحدة)
            // Build the consumption map for this single employee (single query)
            $consumptionMap = LeaveConsumptionService::buildConsumptionMap([$employee->id]);

            $payload = [];

            foreach ($leaveTypes as $leaveType) {

                // تحقق من أهلية الموظف لهذا النوع من الإجازة
                // Check if the employee is eligible for this leave type
                if (!LeaveEligibilityService::isEligible($employee, $leaveType)) {
                    continue;
                }

                $targetMonth = $this->resolveTargetMonth($leaveType, $currentMonth);

                // احتساب الأيام المستحقة بالتناسب بناءً على تاريخ الالتحاق
                // Calculate prorated entitlement based on the employee's joining date
                $entitledDays = LeaveProrationService::calculateEntitlement(
                    $leaveType,
                    $employee->join_date,
                    $currentYear,
                    $targetMonth
                );

                // جلب الاستهلاك الفعلي من الخريطة (صفر في الغالب لموظف جديد)
                // Resolve actual consumption from the pre-built map (usually zero for a new employee)
                $consumption = LeaveConsumptionService::resolve(
                    $consumptionMap,
                    $employee->id,
                    $leaveType->id,
                    $currentYear
                );

                $payload[] = $this->buildPayloadRecord(
                    $employee->id,
                    $employee->branch_id,
                    $leaveType->id,
                    $currentYear,
                    $targetMonth,
                    $entitledDays,
                    (float) $leaveType->count_days,
                    $consumption['used'],
                    $consumption['pending']
                );
            }

            if (!empty($payload)) {
                LeaveBalance::insert($payload);
            }
        });

        Log::info("Leave balances initialized for new employee [{$employee->id}].");
    }

    /**
     * Initialize leave balances for a single newly-created leave type across all eligible employees.
     * تهيئة أرصدة الإجازة لنوع إجازة جديد لجميع الموظفين المؤهلين.
     *
     * يُستخدم من داخل LeaveTypeObserver عند إنشاء نوع إجازة جديد.
     * This method is called from LeaveTypeObserver when a new leave type is created.
     * It processes all active employees in chunks to avoid memory exhaustion.
     *
     * @param  LeaveType $leaveType  نوع الإجازة الجديد
     * @return void
     * @throws \Throwable
     */
    public function handleForNewLeaveType(LeaveType $leaveType): void
    {
        $now          = Carbon::now();
        $currentYear  = $now->year;
        $currentMonth = $now->month;

        // تحميل علاقة الفروع مسبقاً لتجنب N+1 داخل LeaveEligibilityService
        // Eager-load branches relation to prevent N+1 inside LeaveEligibilityService
        $leaveType->loadMissing(['branches' => function ($query) {
            $query->select('branches.id');
        }]);

        $targetMonth = $this->resolveTargetMonth($leaveType, $currentMonth);

        DB::transaction(function () use ($leaveType, $currentYear, $currentMonth, $targetMonth) {

            Employee::query()
                ->active()
                ->select(['id', 'branch_id', 'has_employee_pass', 'join_date'])
                ->chunkById(self::CHUNK_SIZE, function ($employees) use ($leaveType, $currentYear, $currentMonth, $targetMonth) {

                    $employeeIds    = $employees->pluck('id')->all();
                    $consumptionMap = LeaveConsumptionService::buildConsumptionMap($employeeIds);

                    $payload = [];

                    foreach ($employees as $employee) {

                        // تحقق من أهلية الموظف لهذا النوع من الإجازة
                        // Check if the employee is eligible for this leave type
                        if (!LeaveEligibilityService::isEligible($employee, $leaveType)) {
                            continue;
                        }

                        $entitledDays = LeaveProrationService::calculateEntitlement(
                            $leaveType,
                            $employee->join_date,
                            $currentYear,
                            $targetMonth
                        );

                        $consumption = LeaveConsumptionService::resolve(
                            $consumptionMap,
                            $employee->id,
                            $leaveType->id,
                            $currentYear
                        );

                        $payload[] = $this->buildPayloadRecord(
                            $employee->id,
                            $employee->branch_id,
                            $leaveType->id,
                            $currentYear,
                            $targetMonth,
                            $entitledDays,
                            (float) $leaveType->count_days,
                            $consumption['used'],
                            $consumption['pending']
                        );
                    }

                    if (!empty($payload)) {
                        LeaveBalance::insert($payload);
                    }
                });
        });

        Log::info("Leave balances initialized for new leave type [{$leaveType->id}] ({$leaveType->name}).");
    }

    /**
     * Flush leave balances in a scoped, safe manner:
     *
     *  - Yearly leave types  → delete all records for the current year (month IS NULL)
     *  - Monthly leave types → delete records for the current month only,
     *                          leaving past months' history and consumption intact.
     *
     * @param int $year
     * @param int $month
     * @return void
     */
    private function clearExistingBalances(int $year, int $month): void
    {
        // Yearly balances: wipe and re-create for the current year
        LeaveBalance::query()
            ->whereNull('month')
            ->where('year', $year)
            ->forceDelete();

        // Monthly balances: wipe the current month only — past months are preserved
        LeaveBalance::query()
            ->whereNotNull('month')
            ->where('year', $year)
            ->where('month', $month)
            ->forceDelete();
    }

    /**
     * Retrieve all active leave types with their associated branches (Eager Loaded).
     * 
     * @return Collection
     */
    private function getPreparedLeaveTypes(): Collection
    {
        return LeaveType::query()
            ->with(['branches' => function ($query) {
                $query->select('branches.id'); // Fetch only IDs to minimize memory usage
            }])
            ->whereIn('type', [LeaveType::TYPE_MONTHLY, LeaveType::TYPE_YEARLY])
            ->where('active', true)
            ->get();
    }

    /**
     * Process employees in manageable chunks and execute bulk inserts.
     *
     * @param Collection $leaveTypes
     * @param int        $year
     * @param int        $month
     * @return void
     */
    private function processEmployeesInChunks(Collection $leaveTypes, int $year, int $month): void
    {
        // Select only required columns to drastically reduce model instantiation overhead
        Employee::query()
            ->active()
            ->select(['id', 'branch_id', 'has_employee_pass', 'join_date'])
            ->chunkById(self::CHUNK_SIZE, function ($employees) use ($leaveTypes, $year, $month) {

                $employeeIds = $employees->pluck('id')->all();

                // Build the consumption map for this chunk in ONE query (zero N+1)
                $consumptionMap = LeaveConsumptionService::buildConsumptionMap($employeeIds);

                $payload = [];

                foreach ($employees as $employee) {
                    foreach ($leaveTypes as $leaveType) {

                        if (!LeaveEligibilityService::isEligible($employee, $leaveType)) {
                            continue;
                        }

                        $targetMonth = $this->resolveTargetMonth($leaveType, $month);

                        $entitledDays = LeaveProrationService::calculateEntitlement(
                            $leaveType,
                            $employee->join_date,
                            $year,
                            $targetMonth
                        );

                        // Resolve real consumption from pre-fetched map (no extra query)
                        $consumption = LeaveConsumptionService::resolve(
                            $consumptionMap,
                            $employee->id,
                            $leaveType->id,
                            $year
                        );

                        $payload[] = $this->buildPayloadRecord(
                            $employee->id,
                            $employee->branch_id,
                            $leaveType->id,
                            $year,
                            $targetMonth,
                            $entitledDays,
                            (float) $leaveType->count_days,
                            $consumption['used'],
                            $consumption['pending']
                        );
                    }
                }

                if (!empty($payload)) {
                    LeaveBalance::insert($payload);
                }
            });
    }

    /**
     * Format the array payload for bulk insertion.
     *
     * @param int      $employeeId
     * @param int      $leaveTypeId
     * @param int      $year
     * @param int|null $month
     * @param float    $entitledDays
     * @param float    $usedDays      Aggregated from approved leave requests
     * @param float    $pendingDays   Aggregated from pending leave requests
     * @return array
     */
    private function buildPayloadRecord(
        int $employeeId,
        ?int $branchId,
        int $leaveTypeId,
        int $year,
        ?int $month,
        float $entitledDays,
        float $supposedDays,
        float $usedDays = 0.0,
        float $pendingDays = 0.0
    ): array {
        $timestamp = now()->toDateTimeString();

        return [
            'employee_id'   => $employeeId,
            'branch_id'     => $branchId,
            'leave_type_id' => $leaveTypeId,
            'year'          => $year,
            'month'         => $month,
            'entitled_days' => $entitledDays,
            'supposed_days' => $supposedDays,
            'used_days'     => $usedDays,
            'pending_days'  => $pendingDays,
            'balance'       => $entitledDays, // Legacy support
            'created_at'    => $timestamp,
            'updated_at'    => $timestamp,
        ];
    }

    /**
     * Determine if the leave type requires a specific month attribute.
     *
     * @param LeaveType $leaveType
     * @param int       $currentMonth
     * @return int|null
     */
    private function resolveTargetMonth(LeaveType $leaveType, int $currentMonth): ?int
    {
        return $leaveType->balance_period === LeaveType::BALANCE_PERIOD_MONTHLY
            ? $currentMonth
            : null;
    }
}
