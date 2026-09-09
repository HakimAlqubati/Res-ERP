<?php

namespace App\Traits\Scopes;

use Illuminate\Database\Eloquent\Builder;

trait BranchScope
{
    /**
     * Scope records by current branch manager
     *
     * Usage:
     *   Employee::forBranchManager()->get();
     *   Branch::forBranchManager('id')->get();
     */
    public function scopeForBranchManager(Builder $query, string $branchColumn = 'branch_id'): Builder
    {
        if (auth()->check() && (isSuperAdmin() || isSystemManager())) {
            return $query;
        }
        
        if (auth()->check() && isBranchManager()) {
            return $query->where($branchColumn, auth()->user()->branch_id);
        }

        return $query;
    }


    /**
     * Scope records by specific branch ID
     *
     * Usage:
     *   Employee::forBranch(5)->get();
     *   Branch::forBranch(5, 'id')->get();
     */
    public function scopeForBranch(Builder $query, $branchId, string $branchColumn = 'branch_id'): Builder
    {
        return $query->where($branchColumn, $branchId);
    }

    /**
     * Scope for manager: show only branches that this manager owns
     */
    public function scopeForManager(Builder $query, $managerId = null): Builder
    {
        $managerId = $managerId ?? auth()->id();

        return $query->where('manager_id', $managerId);
    }

    /**
     * Scope records for the authenticated employee (if user is staff).
     *
     * Usage:
     *   Attendance::forEmployee()->get();
     *   LeaveRequest::forEmployee()->get();
     */
    public function scopeForEmployee(Builder $query, string $employeeColumn = 'employee_id'): Builder
    {
        if (auth()->check() && (isSuperAdmin() || isSystemManager() || isBranchManager() )) {
            return $query;
        }
        if (auth()->check() && (isStuff() || isStoreManager()) ) {
            return $query->where($employeeColumn, auth()->user()->employee->id);
        }

        return $query;
    }

    /**
     * Scope يدعم تعدد الفروع — يفلتر بكل فروع المستخدم (الأساسي + الإضافية)
     *
     * Usage:
     *   Employee::forUserBranches()->get();
     *   Order::forUserBranches('branch_id')->get();
     */
    public function scopeForUserBranches(Builder $query, string $branchColumn = 'branch_id'): Builder
    {
        if (auth()->check() && (isSuperAdmin() || isSystemManager())) {
            return $query;
        }

        if (auth()->check()) {
            $branchIds = auth()->user()->all_branch_ids;
            return $query->whereIn($branchColumn, $branchIds);
        }

        return $query;
    }
}
