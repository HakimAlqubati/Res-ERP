<?php

namespace App\Services\Warnings\Handlers;

use App\Services\Warnings\Contracts\WarningHandler;
use App\Services\Warnings\Support\HierarchyRepository;

final class MissedCheckinHandler implements WarningHandler
{
    /** @var array<string,mixed> */
    protected array $options = [];

    public function __construct(
        private readonly HierarchyRepository $hierarchy,
    ) {}

    /** اسم فريد للنوع (للتتبع ولوحات المراقبة) */
    public function key(): string
    {
        return 'missed-checkin';
    }

    /** تمرير خيارات من الأمر (user, limit, branch_id, department_id, supervisor_ids …) */
    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    /**
     * المرحلة 1: إثبات مسار جلب المشرفين → المرؤوسين (بدون منطق حضور)
     * لاحقًا نضيف: ShiftResolver + AttendanceProbe + DigestBuilder + إرسال للمشرف.
     */
    public function handle(): array
    {
        $sent = 0;
        $failed = 0;

        // إعداد فلاتر اختيارية للمشرفين
        $filters = [];
        if (!empty($this->options['branch_id'])) {
            $filters['branch_id'] = (int) $this->options['branch_id'];
        }
        if (!empty($this->options['department_id'])) {
            $filters['department_id'] = (int) $this->options['department_id'];
        }
        if (!empty($this->options['supervisor_ids'])) {
            $filters['only_ids'] = (array) $this->options['supervisor_ids'];
        }

        foreach ($this->hierarchy->supervisors($filters) as $supervisor) {
            // لاحقًا: سنكوّن Digest لكل مشرف
            foreach ($this->hierarchy->subordinatesOf($supervisor) as $employee) {
                // لاحقًا: ShiftResolver للتأكد أن اليوم عمل للموظف + حساب نافذة السماح
                // لاحقًا: AttendanceProbe للتحقق من وجود check-in قبل المهلة
                // لاحقًا: إذا Missed → نضيفه لقائمة المشرف
            }
        }

        return [$sent, $failed];
    }
}
