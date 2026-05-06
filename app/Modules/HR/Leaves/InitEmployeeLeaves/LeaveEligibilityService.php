<?php

namespace App\Modules\HR\Leaves\InitEmployeeLeaves;

use App\Models\Employee;
use App\Models\LeaveType;

/**
 * Class LeaveEligibilityService
 * خدمة التحقق من أهلية الموظف للإجازة
 *
 * Handles the business logic to determine if a specific employee
 * is eligible for a given leave type based on HR policies.
 * تتولى منطق الأعمال للتحقق مما إذا كان الموظف مؤهلاً
 * للحصول على نوع إجازة معين وفقاً لسياسات الموارد البشرية.
 *
 * @package App\Modules\HR\Leaves\InitEmployeeLeaves
 */
class LeaveEligibilityService
{
    /**
     * Determine if the employee satisfies the constraints of the leave type.
     * التحقق من استيفاء الموظف لجميع شروط نوع الإجازة.
     *
     * يتحقق من شرطين أساسيين بالتسلسل:
     * 1. شرط الفئة المستهدفة (مثل: وافد بتصريح عمل)
     * 2. شرط الفرع المخصص لنوع الإجازة
     *
     * @param Employee  $employee   الموظف المراد فحص أهليته
     * @param LeaveType $leaveType  نوع الإجازة المطلوب التحقق منه
     * @return bool                 true إذا كان الموظف مؤهلاً، false إذا لم يكن
     */
    public static function isEligible(Employee $employee, LeaveType $leaveType): bool
    {
        // فحص شرط الفئة المستهدفة أولاً (مثل: وافد بتصريح عمل)
        // Check the applicability constraint first (e.g., Expats with EP)
        if (!self::passesApplicabilityConstraint($employee, $leaveType)) {
            return false;
        }

        // فحص شرط الفرع (هل الإجازة متاحة لفرع الموظف؟)
        // Check the branch constraint (is the leave available for the employee's branch?)
        if (!self::passesBranchConstraint($employee, $leaveType)) {
            return false;
        }

        // الموظف يستوفي جميع الشروط → مؤهل للإجازة
        // Employee satisfies all constraints → eligible
        return true;
    }

    /**
     * Check if the employee matches the specific applicability category (e.g., Expats).
     * التحقق من انتماء الموظف للفئة المستهدفة من نوع الإجازة (مثل: الوافدون بتصريح عمل).
     *
     * @param Employee  $employee   الموظف المراد فحصه
     * @param LeaveType $leaveType  نوع الإجازة
     * @return bool                 true إذا تطابقت الفئة أو كانت الإجازة للجميع
     */
    private static function passesApplicabilityConstraint(Employee $employee, LeaveType $leaveType): bool
    {
        // إذا كانت الإجازة مخصصة للوافدين الحاملين لتصريح عمل فقط →
        // تحقق من وجود تصريح العمل للموظف
        // If the leave is only for Expats with Employment Pass →
        // check if the employee holds an employment pass
        if ($leaveType->applicable_to === LeaveType::APPLICABLE_EXPAT_WITH_EP) {
            return (bool) $employee->has_employee_pass;
        }

        // الإجازة متاحة للجميع → الموظف مؤهل تلقائياً
        // Leave is applicable to all → employee passes by default (APPLICABLE_ALL)
        return true;
    }

    /**
     * Check if the leave type is available for the employee's designated branch.
     * التحقق من توفر نوع الإجازة لفرع الموظف المحدد.
     *
     * @param Employee  $employee   الموظف المراد فحص فرعه
     * @param LeaveType $leaveType  نوع الإجازة
     * @return bool                 true إذا كانت الإجازة متاحة لفرع الموظف
     */
    private static function passesBranchConstraint(Employee $employee, LeaveType $leaveType): bool
    {
        // إذا كانت الإجازة متاحة لجميع الفروع → لا حاجة لمزيد من الفحص
        // If the leave type is available for all branches → no further check needed
        if ($leaveType->all_branches) {
            return true;
        }

        // استخراج معرّفات الفروع المسموح بها (يُفترض تحميل علاقة branches مسبقاً لتجنب مشكلة N+1)
        // Extract the IDs of allowed branches (assumes 'branches' relation is eager-loaded to prevent N+1 queries)
        $allowedBranchIds = $leaveType->branches->pluck('id')->toArray();

        // التحقق من أن فرع الموظف ضمن الفروع المسموح بها
        // Check if the employee's branch is within the allowed branches
        return in_array($employee->branch_id, $allowedBranchIds, true);
    }
}
