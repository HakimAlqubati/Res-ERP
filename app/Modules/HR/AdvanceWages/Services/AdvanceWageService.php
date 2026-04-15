<?php

namespace App\Modules\HR\AdvanceWages\Services;

use App\Models\AdvanceWage;
use App\Models\Employee;
use App\Modules\HR\AdvanceWages\Interfaces\AdvanceWageServiceInterface;
use App\Services\HR\Payroll\PayrollLockGuard;
use Illuminate\Pagination\LengthAwarePaginator;
use InvalidArgumentException;

class AdvanceWageService implements AdvanceWageServiceInterface
{
    public function __construct(protected PayrollLockGuard $payrollLockGuard)
    {
    }

    public function getAll(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = AdvanceWage::query()->with(['employee', 'creator', 'approver', 'settledPayroll']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['year'])) {
            $query->where('year', $filters['year']);
        }
        if (!empty($filters['month'])) {
            $query->where('month', $filters['month']);
        }
        if (!empty($filters['employee_id'])) {
            $query->forEmployee($filters['employee_id']);
        }
        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        return $query->latest()->paginate($perPage);
    }

    public function findById(int $id): AdvanceWage
    {
        return AdvanceWage::with(['employee', 'creator', 'approver', 'settledPayroll'])->findOrFail($id);
    }

    public function create(array $data): AdvanceWage
    {
        // Branch ID is inherited from the employee if not explicitly passed
        if (empty($data['branch_id']) && !empty($data['employee_id'])) {
            $data['branch_id'] = Employee::findOrFail($data['employee_id'])->branch_id;
        }

        $data['created_by'] = auth()->id();
        $data['status']     = AdvanceWage::STATUS_PENDING;

        return AdvanceWage::create($data);
    }

    public function update(AdvanceWage $advanceWage, array $data): AdvanceWage
    {
        $this->ensureNotLocked($advanceWage);

        if ($advanceWage->status !== AdvanceWage::STATUS_PENDING) {
            throw new InvalidArgumentException(__('Only pending advance wages can be updated.'));
        }

        $advanceWage->update($data);
        return $advanceWage;
    }

    public function delete(AdvanceWage $advanceWage): void
    {
        $this->ensureNotLocked($advanceWage);

        $advanceWage->delete();
    }

    public function approve(AdvanceWage $advanceWage): AdvanceWage
    {
        $this->ensureNotLocked($advanceWage);

        if (!in_array($advanceWage->status, [AdvanceWage::STATUS_PENDING, AdvanceWage::STATUS_CANCELLED])) {
            throw new InvalidArgumentException(__('Only pending or cancelled advance wages can be approved.'));
        }

        $advanceWage->update([
            'status'      => AdvanceWage::STATUS_SETTLED,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return $advanceWage;
    }

    public function cancel(AdvanceWage $advanceWage): AdvanceWage
    {
        $this->ensureNotLocked($advanceWage);

        if (!in_array($advanceWage->status, [AdvanceWage::STATUS_PENDING, AdvanceWage::STATUS_SETTLED])) {
            throw new InvalidArgumentException(__('Only pending or settled advance wages can be cancelled.'));
        }

        $advanceWage->cancel();
        return $advanceWage;
    }

    /**
     * Throws an exception if the payroll period is locked.
     */
    protected function ensureNotLocked(AdvanceWage $advanceWage): void
    {
        if ($this->payrollLockGuard->isLocked((int) $advanceWage->employee_id, (int) $advanceWage->year, (int) $advanceWage->month)) {
            throw new InvalidArgumentException(__('The payroll for this period is already locked.'));
        }
    }
}
