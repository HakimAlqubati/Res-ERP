<?php

namespace App\Modules\HR\Attendance\Services;

use App\Models\Employee;
use App\Modules\HR\Attendance\Contracts\AttendanceRepositoryInterface;
use App\Modules\HR\Attendance\Contracts\ShiftResolverInterface;
use App\Modules\HR\Attendance\Services\Validator\Rules\DuplicateCheckInRule;
use App\Modules\HR\Attendance\Services\Validator\Rules\DuplicateTimestampRule;
use App\Modules\HR\Attendance\Services\Validator\Rules\MissingCheckInRule;
use App\Modules\HR\Attendance\Services\Validator\Rules\NearShiftEndRule;
use App\Modules\HR\Attendance\Services\Validator\Rules\OverlappingShiftsRule;
use App\Modules\HR\Attendance\Services\Validator\Rules\ShiftCompletionRule;
use App\Modules\HR\Attendance\Services\Validator\Rules\ShiftConflictRule;
use App\Modules\HR\Attendance\Services\Validator\ValidationContext;
use Carbon\Carbon;

/**
 * خدمة التحقق من قواعد العمل للحضور
 * 
 * تفحص جميع قواعد العمل قبل السماح بتسجيل الحضور بشكل منظم ومرتب
 */
class AttendanceValidator
{
    /** @var array<\App\Modules\HR\Attendance\Services\Validator\ValidationRuleInterface> */
    private array $rules;

    public function __construct(
        private ShiftResolverInterface $shiftResolver,
        private AttendanceRepositoryInterface $repository,
        private AttendanceConfig $config
    ) {
        $this->rules = $this->buildRules();
    }

    /**
     * التحقق من صحة طلب الحضور
     * 
     * @throws \App\Modules\HR\Attendance\Exceptions\AttendanceException
     */
    public function validate(Employee $employee, Carbon $requestTime, ?string $requestType = null, ?int $periodId = null): void
    {
        $this->validateWithContext($employee, $requestTime, $requestType, $periodId);
    }

    /**
     * التحقق من صحة طلب الحضور مع إرجاع السياق
     */
    public function validateWithContext(Employee $employee, Carbon $requestTime, ?string $requestType = null, ?int $periodId = null): ValidationContext
    {
        // 1. تحضير سياق التحقق
        $context = ValidationContext::create(
            $employee,
            $requestTime,
            $periodId,
            $this->shiftResolver,
            $this->repository
        );

        // 2. تطبيق جميع القواعد بالترتيب
        foreach ($this->rules as $rule) {
            $rule->validate($context, $requestType, $periodId);
        }

        return $context;
    }

    /**
     * بناء قائمة قواعد التحقق بالترتيب
     */
    private function buildRules(): array
    {
        return [
            // القاعدة 0: منع التسجيل في نفس الدقيقة
            // new DuplicateTimestampRule(),

            // القاعدة 1: التحقق من اكتمال الشيفت
            new ShiftCompletionRule($this->shiftResolver),

            // القاعدة 1.5: التحقق الصارم من اكتمال الوردية (الخروج الرسمي)
            new Validator\Rules\StrictShiftCompletionRule($this->shiftResolver),

            // القاعدة 2: منع تكرار الدخول
            new DuplicateCheckInRule(),

            // القاعدة 3: منع الخروج بدون دخول
            new MissingCheckInRule(),

            // القاعدة 4: الورديات المتداخلة (بدون سجلات)
            // → الوقت في منطقة الفجوة بين شيفتين ولا يوجد أي سجل حضور
            // → يتطلب من المستخدم اختيار الشيفت المطلوبة
            new OverlappingShiftsRule($this->shiftResolver),

            // القاعدة 5: تعارض الورديات (مع check-in مفتوح)
            // → الموظف لديه check-in مفتوح في شيفت
            // → والوقت الحالي ضمن نطاق شيفت أخرى نشطة
            // → يتطلب الاختيار: checkout من الأولى أو checkin للثانية
            new ShiftConflictRule($this->shiftResolver),

            // القاعدة 6: غموض نوع العملية قرب نهاية الشيفت
            // → لا يوجد أي سجلات للموظف
            // → والوقت قريب من نهاية الشيفت
            // → يتطلب تحديد نوع العملية (checkin/checkout) صراحةً
            new NearShiftEndRule($this->config),
        ];
    }
}
