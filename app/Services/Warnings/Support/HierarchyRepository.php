<?php

namespace App\Services\Warnings\Support;

use App\Models\Employee;
use Illuminate\Support\LazyCollection;

final class HierarchyRepository
{
    /**
     * إرجاع المشرفين الذين لديهم مرؤوسون نشطون.
     * يدعم فلاتر اختيارية: branch_id, department_id, only_ids
     */
    public function supervisors(array $filters = []): LazyCollection
    {
        $q = Employee::query()
            ->active()
            ->whereHas('subordinates', fn($sq) => $sq->active());

        if (!empty($filters['branch_id'])) {
            $q->where('branch_id', (int) $filters['branch_id']);
        }
        if (!empty($filters['department_id'])) {
            $q->where('department_id', (int) $filters['department_id']);
        }
        if (!empty($filters['only_ids']) && is_array($filters['only_ids'])) {
            $q->whereIn('id', $filters['only_ids']);
        }

        return $q->orderBy('id')->cursor();
    }

    /**
     * إرجاع مرؤوسي مشرف معيّن (نشطون) كـ LazyCollection
     */
    public function subordinatesOf(Employee $supervisor): LazyCollection
    {
        return $supervisor->subordinates()
            ->active()
            ->orderBy('id')
            ->cursor();
    }
}
