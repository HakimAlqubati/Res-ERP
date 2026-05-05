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
        DB::transaction(function () {
            $this->clearExistingBalances();

            $leaveTypes = $this->getPreparedLeaveTypes();
            $now = Carbon::now();
            $currentYear = $now->year;
            $currentMonth = $now->month;

            $this->processEmployeesInChunks($leaveTypes, $currentYear, $currentMonth);
        });

        Log::info('Leave balances initialization completed successfully.');
    }

    /**
     * Flush the current leave balances.
     * Uses forceDelete() assuming SoftDeletes is active, or truncate() if preferred.
     *
     * @return void
     */
    private function clearExistingBalances(): void
    {
        LeaveBalance::query()->forceDelete();
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
