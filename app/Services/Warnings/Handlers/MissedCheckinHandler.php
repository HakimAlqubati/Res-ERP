<?php

namespace App\Services\Warnings\Handlers;

use App\Enums\Warnings\WarningLevel;
use App\Facades\Warnings;
use App\Models\AppLog;
use App\Models\User;
use App\Services\Warnings\Contracts\WarningHandler;
use App\Services\Warnings\Support\AttendanceProbe;
use App\Services\Warnings\Support\EnvContext;
use App\Services\Warnings\Support\HierarchyRepository;
use App\Services\Warnings\Support\RecipientsService;
use App\Services\Warnings\Support\ReportUrlResolver;
use App\Services\Warnings\Support\ShiftResolver;
use App\Services\Warnings\WarningPayload;
use Carbon\Carbon;

final class MissedCheckinHandler implements WarningHandler
{
    /**
     * خيارات افتراضية.
     *
     * - notify_employees: عند التفعيل سيتم إرسال إشعار للموظف نفسه.
     * - نرسل للمشرف إشعارًا منفصلًا لكل موظف لديه مخالفات في اليوم المحدد.
     */
    private array $options = [
        'user'           => null,   // فلترة اختبارية على user محدد (id أو email)
        'limit'          => 100,    // حد أعلى لعدد المشرفين (Users) أثناء الاختبار
        'date'           => null,   // Y-m-d (افتراضي اليوم)
        'grace'          => 15,     // مهلة السماح بالدقائق
        'branch_id'      => null,   // فلتر هرمي
        'department_id'  => null,   // فلتر هرمي
        'supervisor_ids' => null,   // فلتر هرمي

        // سلوك الإشعارات:
        'notify_employees' => true, // إشعار الموظف نفسه
    ];

    public function __construct(
        private readonly HierarchyRepository $hierarchy,
        private readonly RecipientsService $recipients,
        private readonly ReportUrlResolver $urls,
        private readonly EnvContext $env,
        private readonly ShiftResolver $shiftResolver,
        private readonly AttendanceProbe $probe,
    ) {}

    public function key(): string
    {
        return 'missed-checkin';
    }

    public function setOptions(array $options): void
    {
        $this->options = array_replace($this->options, $options);
    }

    /**
     * المنطق الرئيسي:
     * - نجلب المشرفين وفق الفلاتر.
     * - لكل مشرف: نفحص المرؤوسين، ونجمع المخالفات لكل موظف على حدة.
     * - نرسل للمشرف إشعارًا منفصلًا لكل موظف (يتضمن جميع فترات اليوم لذلك الموظف).
     * - (اختياري) نرسل إشعارًا للموظف نفسه.
     *
     * @return array{0:int,1:int} [sent, failed]
     */
    public function handle(): array
    {
        $sent   = 0;
        $failed = 0;

        $date  = $this->options['date'] ? Carbon::parse($this->options['date']) : Carbon::today();
        $grace = (int) ($this->options['grace'] ?? 15);

        // فلاتر الهرمية
        $filters = [];
        foreach (['branch_id', 'department_id', 'supervisor_ids'] as $k) {
            if (!empty($this->options[$k])) {
                $filters[$k] = $this->options[$k];
            }
        }

        // جلب المشرفين (كوحدات Employee)
        /** @var \Illuminate\Support\Collection<int,\App\Models\Employee> $supervisors */
        $supervisors = collect($this->hierarchy->supervisors($filters));
        if ($supervisors->isEmpty()) {
            return [0, 0];
        }

        // خريطة (user_id => supervisor) مع توثيق لتسكت Intelephense
        /** @var \Illuminate\Support\Collection<int,\App\Models\Employee> $supervisorByUserId */
        $supervisorByUserId = $supervisors
            ->filter(fn($sup) => $sup->user instanceof \App\Models\User)
            ->keyBy(fn($sup) => $sup->user?->id); // null-safe: العناصر بلا user تُستبعد كمفتاح

        // قائمة Users للمشرفين (مع تطبيق --user و limit)
        $users = $supervisors
            ->pluck('user')
            ->filter(fn($u) => $u instanceof User);

        $users = $this->recipients->normalize($users);
        $users = $this->recipients->filterByOptionUser($users, $this->options['user'] ?? null);
        $limit = (int)($this->options['limit'] ?? 100) ?: 100;
        $users = $users->take(max(1, $limit));

        $tenantId = $this->env->tenantId();

        // حلقة المشرفين (users)
        foreach ($users as $supUser) {
            if (!$supUser instanceof User) {
                continue;
            }

            $sup = $supervisorByUserId->get($supUser->id);
            if (!$sup) {
                continue;
            }

            // في وضع الاختبار، قد نقيّد على مستخدم واحد
            if ($this->options['user'] && !$this->recipients->matchesOptionUser($supUser, $this->options['user'])) {
                continue;
            }

            // سنجمع لكل موظف فترات اليوم التي فاتته
            // بنية: [emp_id => ['employee'=>[], 'periods'=>[...]]]
            $missedByEmp = [];

            // مرؤوسو المشرف
            $subs = $this->hierarchy->subordinatesOf($sup);

            foreach ($subs as $emp) {
                // استنتاج فترات العمل مع مهلة السماح
                $shifts = $this->shiftResolver->resolve($emp, $date, $grace);

                foreach ($shifts as $s) {
                    $periodId = $s['period']->id;
                    $deadline = $s['grace_deadline'];


                    // نحاول جلب بداية ونهاية الفترة ككائنات Carbon
                    /** @var \Carbon\CarbonInterface|null $start */
                    /** @var \Carbon\CarbonInterface|null $end */
                    $start = $s['start'] ?? null;
                    $end   = $s['end']   ?? ($s['period']->end ?? null);

                    // إن لم تتوفر البداية/النهاية نتجاوز هذه الفترة بحذر
                    if (!$start || !$end) {
                        continue;
                    }

                    // dd($start,$end);
                    // شرطك: لا ترسل (ولا تحسب مخالفة) إلا إذا الوقت الحالي بين بداية ونهاية الفترة
                    $now = now();
                    if ($now->lt($start) || $now->gt($end)) {
                        continue; // خارج نطاق الفترة: لا إرسال
                    }
                    // لا نحكم قبل انتهاء المهلة
                    if (now()->lessThan($deadline)) {
                        continue;
                    }

                    // يوجد checkin قبل المهلة؟ إذًا لا تُحتسب Missed
                    if ($this->probe->hasCheckinBefore($emp, $periodId, $deadline)) {
                        continue;
                    }

                    // TODO: ربط منطق الإجازة إن وُجد داخل نظامك
                    $onLeave = false;

                    /**
                     * (1) إشعار الموظف نفسه (اختياري)
                     */
                    // if (!empty($this->options['notify_employees']) && $emp->user instanceof User) {
                    //     $empUser = $emp->user;

                    //     // في وضع الاختبار، نطبق --user أيضًا على الموظف
                    //     if (!$this->options['user'] || $this->recipients->matchesOptionUser($empUser, $this->options['user'])) {
                    //         $empPayload = WarningPayload::make(
                    //             'Missed Check-in',
                    //             "You missed check-in for '{$s['period']->name}' on {$date->toDateString()} (grace {$grace}m).",
                    //             WarningLevel::Warning
                    //         )
                    //             ->ctx([
                    //                 'tenant_id'      => $tenantId,
                    //                 'date'           => $date->toDateString(),
                    //                 'grace'          => $grace,
                    //                 'employee'       => [
                    //                     'id'          => $emp->id,
                    //                     'name'        => $emp->name,
                    //                     'employee_no' => $emp->employee_no ?? null,
                    //                     'branch'      => $emp->branch?->name,
                    //                 ],
                    //                 'period_id'      => $periodId,
                    //                 'period_label'   => $s['period']->name ?? ('Period #' . $periodId),
                    //                 'shift_start'    => $s['start']->format('Y-m-d H:i:s'),
                    //                 'grace_deadline' => $deadline->format('Y-m-d H:i:s'),
                    //                 'reason'         => 'no_checkin_before_grace',
                    //                 'on_leave'       => $onLeave,
                    //             ])
                    //             // منع التكرار لنفس (tenant/emp/date/period)
                    //             ->scope("missedcheckin:tenant-{$tenantId}:emp-{$emp->id}:date-{$date->toDateString()}:period-{$periodId}")
                    //             // ->url($this->urls->attendanceForEmployee($emp, $date)) // إن أردت رابط الحضور
                    //             ->expires(now()->addHours(6));

                    //         try {
                    //             Warnings::send($empUser, $empPayload);
                    //             $sent++;
                    //         } catch (\Throwable $e) {
                    //             $failed++;
                    //             AppLog::write(
                    //                 'Failed to send missed check-in warning to employee',
                    //                 AppLog::LEVEL_WARNING,
                    //                 'attendance',
                    //                 [
                    //                     'tenant_id'   => $tenantId,
                    //                     'employee_id' => $emp->id,
                    //                     'date'        => $date->toDateString(),
                    //                     'error'       => $e->getMessage(),
                    //                 ]
                    //             );
                    //         }
                    //     }
                    // }

                    /**
                     * (2) تجميع فترات الموظف لإشعار المشرف بشكل منفصل لكل موظف
                     */
                    $missedByEmp[$emp->id]['employee'] = [
                        'id'          => $emp->id,
                        'name'        => $emp->name,
                        'employee_no' => $emp->employee_no ?? null,
                        'branch'      => $emp->branch?->name,
                    ];

                    $missedByEmp[$emp->id]['periods'][] = [
                        'period_id'      => $periodId,
                        'period_label'   => $s['period']->name ?? ('Period #' . $periodId),
                        'shift_start'    => $s['start']->format('Y-m-d H:i:s'),
                        'grace_deadline' => $deadline->format('Y-m-d H:i:s'),
                        'reason'         => 'no_checkin_before_grace',
                        'on_leave'       => $onLeave,
                    ];
                }
            }

            /**
             * (3) إرسال إشعار للمشرف — إشعار منفصل لكل موظف لديه مخالفات
             */
            foreach ($missedByEmp as $empId => $bundle) {
                $empInfo = $bundle['employee'] ?? null;
                $periods = $bundle['periods']  ?? [];

                if (!$empInfo || empty($periods)) {
                    continue;
                }

                $title = 'Missed Check-in (Employee)';
                $body  = "Employee {$empInfo['name']} missed check-in on {$date->toDateString()} (grace {$grace}m).";

                $payload = WarningPayload::make($title, $body, WarningLevel::Warning)
                    ->ctx([
                        'tenant_id'  => $tenantId,
                        'date'       => $date->toDateString(),
                        'grace'      => $grace,
                        'supervisor' => [
                            'id'     => $sup->id,
                            'name'   => $sup->name,
                            'branch' => $sup->branch?->name,
                        ],
                        'employee'   => $empInfo,
                        'periods'    => $periods, // جميع فترات اليوم للموظف
                    ])
                    // منع تكرار إشعار نفس (tenant/supervisor/employee/date)
                    ->scope("missedcheckin:tenant-{$tenantId}:sup-{$sup->id}:emp-{$empId}:date-{$date->toDateString()}")
                    ->expires(now()->addHours(6));

                try {
                    Warnings::send($supUser, $payload);
                    $sent++;
                } catch (\Throwable $e) {
                    $failed++;
                    AppLog::write(
                        'Failed to send per-employee missed check-in warning to supervisor',
                        AppLog::LEVEL_WARNING,
                        'attendance',
                        [
                            'tenant_id'     => $tenantId,
                            'supervisor_id' => $sup->id,
                            'employee_id'   => $empId,
                            'date'          => $date->toDateString(),
                            'error'         => $e->getMessage(),
                        ]
                    );
                }
            }
        }

        return [$sent, $failed];
    }
}
