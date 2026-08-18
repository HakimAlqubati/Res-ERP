<?php

namespace App\Modules\HR\Payroll\Services;

use App\Enums\HR\Payroll\SalaryAllocationRule;
use App\Models\Employee;
use App\Models\EmployeeBranchLog;
use App\Models\PayrollRun;
use App\Models\Setting;
use App\Modules\HR\AttendanceReports\Contracts\AttendanceReportInterface;
use App\Modules\HR\Payroll\Contracts\PayrollSimulatorInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PayrollSimulationService implements PayrollSimulatorInterface
{
    public function __construct(
        protected AttendanceReportInterface $reportManager,
        protected SalaryCalculatorService $salaryCalculatorService,
        protected WeeklyLeaveSegmentAllocator $weeklyLeaveAllocator,
    ) {}

    // ─────────────────────────────────────────────────────────────
    // Public API
    // ─────────────────────────────────────────────────────────────

    /**
     * محاكاة الرواتب لموظفين محددين (بدون حفظ في قاعدة البيانات).
     */
    public function simulateForEmployees(
        array $employeeIds,
        int $year,
        int $month,
        ?int $branchId = null,
        ?Carbon $optionalStart = null,
        ?Carbon $optionalEnd = null,
        bool $forceMultiSegment = false, // يُمرَّر من PayrollRunService عند معرفة وجود انتقال فرع
    ): array {
        $periodStart = $optionalStart ?? Carbon::create($year, $month, 1)->startOfMonth();
        $periodEnd   = $optionalEnd   ?? Carbon::create($year, $month, 1)->endOfMonth();

        // تقليص نهاية الفترة إذا كان الشهر الحالي ولم يُحدد تاريخ نهاية صريح
        if (is_null($optionalEnd) && $year == now()->year && $month == now()->month) {
            $periodEnd = now()->endOfDay();
        }

        $employees = $this->resolveEmployees($employeeIds, $branchId, $periodStart, $periodEnd);
        $segments  = $this->buildSegments($employees, $periodStart, $periodEnd, $branchId);

        return $this->processSegments($segments, $year, $month, $periodStart, $periodEnd, $forceMultiSegment);
    }

    /**
     * محاكاة الرواتب بناءً على PayrollRun موجود (بدون حفظ في قاعدة البيانات).
     */
    public function simulateForRunEmployees(PayrollRun $run, array $employeeIds): array
    {
        $periodStart = Carbon::parse($run->period_start_date)->startOfDay();
        $periodEnd   = Carbon::parse($run->period_end_date)->endOfDay();

        $employees = Employee::whereIn('id', $employeeIds)->get();
        $segments  = $this->buildSegments($employees, $periodStart, $periodEnd, $run->branch_id);

        return $this->processSegments($segments, $run->year, $run->month, $periodStart, $periodEnd);
    }

    // ─────────────────────────────────────────────────────────────
    // Private Helpers
    // ─────────────────────────────────────────────────────────────

    /**
     * جلب الموظفين المؤهلين، مع تصفية إضافية بالفرع إذا طُلب.
     */
    private function resolveEmployees(array $employeeIds, ?int $branchId, Carbon $periodStart, Carbon $periodEnd): Collection
    {
        $query = Employee::whereIn('id', $employeeIds);

        if ($branchId) {
            $idsInBranch = EmployeeBranchLog::getEmployeesForBranchInRange($branchId, $periodStart, $periodEnd);
            $query->whereIn('id', $idsInBranch);
        }

        return $query->get()
            ->filter(fn(Employee $employee) => $this->isEmployeeOwnedByBranchForPayroll($employee, $branchId, $periodStart, $periodEnd))
            ->values();
    }

    /**
     * تحويل الموظفين إلى فترات عمل (Segments) بناءً على قاعدة التوزيع المفعّلة.
     */
    private function buildSegments(Collection $employees, Carbon $periodStart, Carbon $periodEnd, ?int $branchId): Collection
    {
        return $employees->flatMap(
            fn(Employee $employee) => EmployeeBranchLog::getSalarySegments(
                $employee,
                $periodStart,
                $periodEnd,
                $this->segmentTargetBranchId($employee, $branchId),
            )
                ->map(fn($seg) => ['employee' => $employee, 'log' => (object) $seg])
        );
    }

    /**
     * For proportional allocation, the selected last/current branch owns the
     * simulation, while the result still contains all branch split periods.
     */
    private function segmentTargetBranchId(Employee $employee, ?int $branchId): ?int
    {
        if (! $branchId) {
            return null;
        }

        return $employee->getEffectiveSalaryAllocationRule() === SalaryAllocationRule::PROPORTIONAL
            ? null
            : $branchId;
    }

    private function isEmployeeOwnedByBranchForPayroll(Employee $employee, ?int $branchId, Carbon $periodStart, Carbon $periodEnd): bool
    {
        if (! $branchId || $employee->getEffectiveSalaryAllocationRule() !== SalaryAllocationRule::PROPORTIONAL) {
            return true;
        }

        $ownerSegment = EmployeeBranchLog::getSalarySegments(
            $employee,
            $periodStart,
            $periodEnd,
            null,
            SalaryAllocationRule::LAST_BRANCH,
        )->first();

        return (int) ($ownerSegment['branch_id'] ?? 0) === $branchId;
    }

    /**
     * معالجة كل فترة عمل واحتساب الراتب الخاص بها.
     * هذا هو الكود المشترك بين simulateForEmployees و simulateForRunEmployees.
     */
    private function processSegments(Collection $segments, int $year, int $month, Carbon $periodStart, Carbon $periodEnd, bool $forceMultiSegment = false): array
    {
        $monthDays = (int) $periodStart->daysInMonth;
        $results   = [];

        foreach ($segments->groupBy(fn($s) => $s['employee']->id) as $employeeSegments) {
            /** @var Employee $employee */
            $employee       = $employeeSegments->first()['employee'];
            $isMultiSegment = $forceMultiSegment || $employeeSegments->count() > 1;

            $monthlySalary = (float) ($employee->salary ?? 0);
            if ($monthlySalary <= 0) {
                $results[] = $this->failedResult($employee, 'Salary not set or zero.');
                continue;
            }

            $dailyHours = (int) ($employee->working_hours ?? 0);
            $workDays   = (int) ($employee->working_days ?? 0);
            if ($dailyHours <= 0 || $workDays <= 0) {
                throw new \InvalidArgumentException(
                    "Missing working_hours or working_days for employee [{$employee->name}] (No: {$employee->employee_no})"
                );
            }

            // 1. جلب بيانات الحضور الخاصة بكل Segment بمعزل (وقائع خام: حضور/غياب/ساعات)
            $segmentData = $employeeSegments->values()->map(fn($segment) => [
                'log'  => $segment['log'],
                'data' => $this->fetchAttendance($employee, $segment['log']->start, $segment['log']->end),
            ]);

            // 2. لموظفي تعدد الفروع: احسب الإجازة الأسبوعية مرة واحدة فقط على الفترة
            //    الكاملة (مصدر حقيقة واحد، بدون تجزئة)، ثم وزّعها تناسبيًا على كل Segment.
            if ($isMultiSegment) {
                $unifiedResult = $this->computeUnifiedWeeklyLeave($employee, $periodStart, $periodEnd);

                $segmentStats = $segmentData->map(function ($item) {
                    $wlc = $item['data']['statistics']['weekly_leave_calculation'] ?? [];
                    return [
                        'worked_days' => (int) ($wlc['analysis']['worked_days'] ?? 0),
                        'absent_days' => (int) ($item['data']['statistics']['absent'] ?? 0),
                    ];
                })->all();

                $allocations = $this->weeklyLeaveAllocator->allocate($unifiedResult, $segmentStats);

                $segmentData = $segmentData->values()->map(function ($item, $i) use ($allocations) {
                    if (isset($item['data']['statistics']['weekly_leave_calculation'])) {
                        $item['data']['statistics']['weekly_leave_calculation']['result'] = $allocations[$i]['result'];
                        $item['data']['statistics']['weekly_leave_calculation']['analysis']['earned_leave_days']
                            = $allocations[$i]['earned_leave_days'];
                    }
                    return $item;
                });
            }

            // 3. احتساب الراتب لكل Segment باستخدام بياناته (المُوزَّعة تناسبيًا إن كان متعدد الفروع)
            foreach ($segmentData as $item) {
                $log             = $item['log'];
                $attendanceArray = $item['data'];

                $totalApprovedOvertime = $employee->overtimes()
                    ->where('branch_id', $log->branch_id)
                    ->whereYear('date', $year)
                    ->whereMonth('date', $month)
                    ->whereBetween('date', [$log->start, $log->end])
                    ->sum('hours');

                $result = $this->salaryCalculatorService->calculate(
                    employee:              $employee,
                    employeeData:          $attendanceArray,
                    salary:                $monthlySalary,
                    workingDays:           $workDays,
                    dailyHours:            $dailyHours,
                    monthDays:             $monthDays,
                    totalDuration:         $attendanceArray['total_duration_hours']        ?? '0:00:00',
                    totalActualDuration:   $attendanceArray['total_actual_duration_hours'] ?? '0:00:00',
                    totalApprovedOvertime: $totalApprovedOvertime,
                    periodYear:            $year,
                    periodMonth:           $month,
                    periodEnd:             $log->end,
                    periodStart:           $log->start,   // ← تخصيص فترة الفرع بدقة
                    isMultiSegment:        $isMultiSegment,
                );

                $results[] = $this->buildResult($employee, $result, $monthlySalary, $dailyHours, $monthDays, $attendanceArray, $log);
            }
        }

        return $results;
    }

    /**
     * احتساب "الإجازة الأسبوعية" مرة واحدة فقط على كامل فترة الموظف (كل الفروع مجتمعة)،
     * كمصدر حقيقة وحيد يُستخدم لاحقًا لتوزيع الخصم/البدل على الفروع تناسبيًا.
     * لا يوجد أي تجزئة أو تخمين هنا — تمامًا كأن الموظف لم ينتقل فرعًا إطلاقًا.
     */
    private function computeUnifiedWeeklyLeave(Employee $employee, Carbon $periodStart, Carbon $periodEnd): array
    {
        $fullReport = $this->reportManager
            ->getEmployeesRangeReport(collect([$employee]), $periodStart, $periodEnd, true)
            ->get($employee->id);

        return (array) ($fullReport['statistics']['weekly_leave_calculation'] ?? []);
    }

    /**
     * جلب تقرير الحضور لموظف في فترة محددة.
     */
    private function fetchAttendance(Employee $employee, Carbon $start, Carbon $end): array
    {
        $data = $this->reportManager
            ->getEmployeesRangeReport(collect([$employee]), $start, $end, true) // 👈 استبعاد التحضيرات no_shift
            ->get($employee->id);

        return (array) $data?->toArray();
    }

    /**
     * بناء نتيجة ناجحة موحدة للموظف.
     */
    private function buildResult(Employee $employee, array $result, float $monthlySalary, int $dailyHours, int $monthDays, array $attendance, object $log): array
    {
        $netSalary      = $result['net_salary'];
        $carryForwarded = $result['carry_forwarded'] ?? 0;

        return [
            'success'                     => true,
            'message'                     => 'Simulation completed successfully.',
            'employee_id'                 => $employee->id,
            'employee_no'                 => $employee->employee_no,
            'name'                        => $employee->name,
            'working_days'                => $result['working_days'],
            'working_hours'               => $dailyHours,
            'monthly_salary'              => $monthlySalary,
            'daily_salary'                => round($result['daily_rate'], 2),
            'hourly_salary'               => $result['hourly_rate'],
            'month_days'                  => $monthDays,
            'attendance_statistics'       => $attendance['statistics'] ?? [],
            'total_approved_overtime'     => $result['total_approved_overtime'],
            'total_actual_duration_hours' => $result['total_actual_duration'],
            'total_duration_hours'        => $result['total_duration'],
            'missing_hours'               => $result['missing_hours'],
            'missing_hours_deduction'     => $result['missing_hours_deduction'],
            'early_departure_hours'       => $result['early_departure_hours'],
            'early_departure_deduction'   => $result['early_departure_deduction'],
            'total_deduction'             => $result['total_deductions'] ?? 0,
            'tax'                         => $result['tax'] ?? 0,
            'late_hours'                  => $result['late_hours'],
            'transactions'                => $result['transactions'] ?? [],
            'dynamic_deductions'          => $result['dynamic_deductions'] ?? [],
            'penalty_total'               => $result['penalty_total'] ?? 0,
            'penalties'                   => $result['penalties'] ?? [],
            'daily_rate_method'           => $result['daily_rate_method'] ?? '',
            'data' => [
                'base_salary'       => $result['base_salary'],
                'gross_salary'      => $result['gross_salary'],
                'net_salary'        => $netSalary,
                'is_negative'       => $result['is_negative'],
                'absence_deduction' => $result['absence_deduction'],
                'overtime_amount'   => $result['overtime_amount'],
                'allowances'        => $result['allowances'],
                'allowance_total'   => $result['allowance_total'],
                'carry_forwarded'   => $carryForwarded,
                'period_start'      => $log->start->toDateString(),
                'period_end'        => $log->end->toDateString(),
                'transactions'      => $result['transactions'] ?? [],
            ],
        ];
    }

    /**
     * بناء نتيجة فشل موحدة للموظف.
     */
    private function failedResult(Employee $employee, string $reason): array
    {
        return [
            'success'     => false,
            'message'     => "Simulation failed for [{$employee->name}] (No: {$employee->employee_no}): {$reason}",
            'employee_id' => $employee->id,
            'employee_no' => $employee->employee_no,
            'name'        => $employee->name,
            'data'        => null,
        ];
    }
}