<?php

namespace App\Modules\HR\Payroll\Repositories;

use App\Models\Payroll;

class PayrollRepository
{
    public function create(array $data): Payroll
    {
        return Payroll::create($data);
    }

    public function find($id): ?Payroll
    {
        return Payroll::find($id);
    }

    public function byEmployee($employeeId, $year = null, $month = null)
    {
        $query = Payroll::where('employee_id', $employeeId);
        if ($year)  $query->where('year', $year);
        if ($month) $query->where('month', $month);
        return $query->get();
    }

    public function approve($id, $userId)
    {
        $payroll = $this->find($id);
        if ($payroll) {
            $payroll->status = Payroll::STATUS_APPROVED;
            $payroll->approved_by = $userId;
            $payroll->approved_at = now();
            $payroll->save();
        }
        return $payroll;
    }

    public function pay($id, $userId)
    {
        $payroll = $this->find($id);
        if ($payroll) {
            $payroll->status = Payroll::STATUS_PAID;
            $payroll->paid_by = $userId;
            $payroll->paid_at = now();
            $payroll->save();
        }
        return $payroll;
    }

    // أضف المزيد من الدوال حسب الحاجة
}
