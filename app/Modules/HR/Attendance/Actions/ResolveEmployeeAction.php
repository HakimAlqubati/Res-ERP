<?php

namespace App\Modules\HR\Attendance\Actions;

use App\Models\Employee;

/**
 * Action لتحديد الموظف من الـ payload
 * 
 * يدعم ثلاث طرق للتعريف:
 * 1. كائن Employee مباشرة
 * 2. معرف الموظف (employee_id)
 * 3. رقم RFID
 */
class ResolveEmployeeAction
{
    /**
     * تنفيذ العملية
     */
    public function execute(array $payload): ?Employee
    {
        // الطريقة 1: كائن Employee موجود بالفعل
        if ($this->hasEmployeeObject($payload)) {
            return $payload['employee'];
        }

        // الطريقة 2: معرف الموظف
        if ($this->hasEmployeeId($payload)) {
            return $this->findById($payload['employee_id']);
        }

        // الطريقة 3: رقم RFID
        if ($this->hasRfid($payload)) {
            return $this->findByRfid($payload['rfid']);
        }

        return null;
    }

    /**
     * التحقق من وجود كائن Employee
     */
    private function hasEmployeeObject(array $payload): bool
    {
        return isset($payload['employee']) && $payload['employee'] instanceof Employee;
    }

    /**
     * التحقق من وجود معرف الموظف
     */
    private function hasEmployeeId(array $payload): bool
    {
        return isset($payload['employee_id']) && !empty($payload['employee_id']);
    }

    /**
     * التحقق من وجود RFID
     */
    private function hasRfid(array $payload): bool
    {
        return isset($payload['rfid']) && !empty($payload['rfid']);
    }

    /**
     * البحث بالمعرف
     */
    private function findById(int $id): ?Employee
    {
        return Employee::find($id);
    }

    /**
     * البحث بـ RFID
     */
    private function findByRfid(string $rfid): ?Employee
    {
        return Employee::where('rfid', $rfid)->first();
    }
}
