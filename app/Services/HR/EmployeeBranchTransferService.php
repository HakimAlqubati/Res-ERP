<?php

declare(strict_types=1);

namespace App\Services\HR;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeBranchLog;
use App\Models\EmployeePeriodHistory;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * EmployeeBranchTransferService
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * المسؤولية الوحيدة لهذه الخدمة: إدارة عملية نقل الموظف بين الفروع بشكل
 * احترافي وآمن وأتوماتيكي، مع توفير بيانات المعاينة (Preview) قبل التنفيذ.
 * ─────────────────────────────────────────────────────────────────────────────
 *
 * تتضمن العملية الخطوات التالية:
 *  1. تجميد سجلات الفترات النشطة (EmployeePeriodHistory):
 *     - تسجيل الـ branch_id القديم (Snapshot) عليها.
 *     - إغلاقها بـ end_date = transfer_start_date - 1 يوم.
 *
 *  2. إغلاق سجل الفرع الحالي (EmployeeBranchLog):
 *     - تعيين end_at = transfer_start_date على السجل المفتوح.
 *
 *  3. إنشاء سجل فرع جديد (EmployeeBranchLog):
 *     - بالفرع الجديد وتاريخ البدء المحدد.
 *
 *  4. تحديث الموظف:
 *     - تغيير branch_id مباشرة على سجل الموظف.
 *
 * ملاحظة: جميع الخطوات تُنفَّذ داخل Database Transaction لضمان الاتساق.
 */
class EmployeeBranchTransferService
{
    // ─────────────────────────────────────────────────────────────────────────
    // Preview — بيانات للعرض قبل التنفيذ
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * يُعيد ملخصاً كاملاً بكل ما سيحدث أثناء عملية النقل.
     * يُستخدم لعرض التحذير قبل التأكيد.
     *
     * @return array{
     *   current_branch: Branch|null,
     *   new_branch: Branch|null,
     *   active_period_histories: Collection,
     *   open_branch_log: EmployeeBranchLog|null,
     *   operations: array<string>,
     * }
     */
    public function preview(Employee $employee, int $newBranchId, string $transferDate): array
    {
        $activePeriodHistories = $employee
            ->periodHistories()
            ->where(function ($q) use ($transferDate) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $transferDate);
            })
            ->with('workPeriod')
            ->get();

        $openBranchLog = $employee->branchLogs()->whereNull('end_at')->first();
        $currentBranch = $employee->branch;
        $newBranch     = Branch::find($newBranchId);

        $operations = $this->buildOperationsList(
            employee: $employee,
            activePeriodHistories: $activePeriodHistories,
            currentBranch: $currentBranch,
            newBranch: $newBranch,
            openBranchLog: $openBranchLog,
            transferDate: $transferDate,
        );

        return compact(
            'currentBranch',
            'newBranch',
            'activePeriodHistories',
            'openBranchLog',
            'operations',
        );
    }

    /**
     * يبني قائمة العمليات المنتظرة بلغة واضحة للمستخدم.
     */
    private function buildOperationsList(
        Employee            $employee,
        Collection          $activePeriodHistories,
        ?Branch             $currentBranch,
        ?Branch             $newBranch,
        ?EmployeeBranchLog  $openBranchLog,
        string              $transferDate,
    ): array {
        $closureDate = Carbon::parse($transferDate)->subDay()->toDateString();
        $ops         = [];

        // ① تجميد سجلات الفترات
        if ($activePeriodHistories->isNotEmpty()) {
            $count   = $activePeriodHistories->count();
            $periods = $activePeriodHistories
                ->map(fn($h) => $h->workPeriod?->name ?? "#$h->id")
                ->join(', ');

            $ops[] = [
                'type'  => 'warning',
                'title' => __('lang.period_histories_will_be_closed', ['count' => $count]),
                'body'  => __('lang.period_histories_closure_detail', [
                    'periods' => $periods,
                    'date'    => $closureDate,
                ]),
            ];

            $ops[] = [
                'type'  => 'info',
                'title' => __('lang.new_shift_required'),
                'body'  => __('lang.new_shift_required_detail', [
                    'branch' => $newBranch?->name,
                ]),
            ];
        } else {
            $ops[] = [
                'type'  => 'success',
                'title' => __('lang.no_active_period_histories'),
                'body'  => __('lang.no_active_period_histories_detail'),
            ];
        }

        // ② إغلاق سجل الفرع المفتوح
        if ($openBranchLog) {
            $ops[] = [
                'type'  => 'warning',
                'title' => __('lang.open_branch_log_will_be_closed'),
                'body'  => __('lang.open_branch_log_closure_detail', [
                    'branch' => $currentBranch?->name,
                    'date'   => $transferDate,
                ]),
            ];
        }

        // ③ إنشاء سجل فرع جديد
        $ops[] = [
            'type'  => 'success',
            'title' => __('lang.new_branch_log_will_be_created'),
            'body'  => __('lang.new_branch_log_detail', [
                'branch' => $newBranch?->name,
                'date'   => $transferDate,
            ]),
        ];

        // ④ تحديث فرع الموظف
        $ops[] = [
            'type'  => 'success',
            'title' => __('lang.employee_branch_will_be_updated'),
            'body'  => __('lang.employee_branch_update_detail', [
                'from' => $currentBranch?->name,
                'to'   => $newBranch?->name,
            ]),
        ];

        return $ops;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Execute — تنفيذ النقل بشكل أتوماتيكي وآمن
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * تُنفِّذ عملية نقل الموظف بالكامل داخل Database Transaction واحدة.
     *
     * @throws \Throwable إذا فشلت أي خطوة، تُلغى جميع التغييرات.
     */
    public function execute(
        Employee $employee,
        int      $newBranchId,
        string   $startAt,
        ?string  $endAt = null
    ): void {
        DB::transaction(function () use ($employee, $newBranchId, $startAt, $endAt) {

            $closureDate = Carbon::parse($startAt)->subDay()->toDateString();

            // dd($newBranchId,$startAt,$endAt,$closureDate);
            // ① حذف سجلات الفترات الحالية (hr_employee_periods) — الأساس
            $employee->employeePeriods()->delete();

            // ② تجميد سجلات التاريخ النشطة (Snapshot + إغلاق end_date)
            $employee->periodHistories()
                ->where(function ($q) use ($startAt) {
                    $q->whereNull('end_date')
                        ->orWhere('end_date', '>=', $startAt);
                })
                ->update([
                    'branch_id'  => $employee->branch_id, // Snapshot للفرع القديم
                    'end_date'   => $closureDate,
                    'updated_by' => Auth::id(),
                ]);

            // ③ إغلاق سجل الفرع المفتوح
            $openLog = $employee->branchLogs()->whereNull('end_at')->first();
            if ($openLog) {
                $finalEndAt = Carbon::parse($closureDate);
                $logStartAt = Carbon::parse($openLog->start_at);

                // التأكد من أن تاريخ النهاية لا يقل عن تاريخ البداية
                if ($finalEndAt->isBefore($logStartAt)) {
                    $finalEndAt = $logStartAt;
                }

                $openLog->update([
                    'end_at' => $finalEndAt->toDateString(),
                ]);
            }

            // ④ إنشاء سجل فرع جديد
            EmployeeBranchLog::create([
                'employee_id' => $employee->id,
                'branch_id'   => $newBranchId,
                'start_at'    => $startAt,
                'end_at'      => $endAt,
                'created_by'  => Auth::id(),
            ]);

            // ⑤ تحديث فرع الموظف
            $employee->update(['branch_id' => $newBranchId]);
        });
    }
}
