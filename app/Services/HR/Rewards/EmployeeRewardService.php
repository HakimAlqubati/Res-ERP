<?php

namespace App\Services\HR\Rewards;

use App\Models\EmployeeReward;
use App\Services\HR\Rewards\Contracts\EmployeeRewardServiceInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Exception;

class EmployeeRewardService implements EmployeeRewardServiceInterface
{
    /**
     * @inheritDoc
     */
    public function getRewardsList(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = EmployeeReward::query()->with([
            'employee:id,name,branch_id',
            'rewardType:id,name',
            'creator:id,name',
            'approver:id,name',
            'rejector:id,name'
        ]);

        // Filter by branch if user is branch manager
        if (isBranchManager()) {
            $query->whereHas('employee', function ($q) {
                $q->where('branch_id', auth()->user()->branch_id);
            });
        }

        if (!empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        if (!empty($filters['incentive_id'])) {
            $query->where('incentive_id', $filters['incentive_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['year'])) {
            $query->where('year', $filters['year']);
        }

        if (!empty($filters['month'])) {
            $query->where('month', $filters['month']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('date', '<=', $filters['date_to']);
        }

        if (!empty($filters['q'])) {
            $search = $filters['q'];
            $query->where(function ($q) use ($search) {
                $q->where('reason', 'like', "%{$search}%")
                  ->orWhereHas('employee', function ($eq) use ($search) {
                      $eq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        return $query->latest('date')->paginate($perPage);
    }

    /**
     * @inheritDoc
     */
    public function createReward(array $data): EmployeeReward
    {
        return EmployeeReward::create($data);
    }

    /**
     * @inheritDoc
     */
    public function updateReward(int $id, array $data): EmployeeReward
    {
        $reward = $this->getRewardById($id);

        if (!$reward) {
            throw new Exception("Employee reward not found.");
        }

        if ($reward->status !== EmployeeReward::STATUS_PENDING) {
            throw new Exception("Only pending rewards can be updated.");
        }

        $reward->update($data);

        return $reward;
    }

    /**
     * @inheritDoc
     */
    public function getRewardById(int $id): ?EmployeeReward
    {
        return EmployeeReward::with([
            'employee:id,name,branch_id',
            'rewardType:id,name',
            'creator:id,name',
            'approver:id,name',
            'rejector:id,name'
        ])->find($id);
    }

    /**
     * @inheritDoc
     */
    public function approveReward(int $id, int $userId): EmployeeReward
    {
        $reward = $this->getRewardById($id);

        if (!$reward) {
            throw new Exception("Employee reward not found.");
        }

        if ($reward->status !== EmployeeReward::STATUS_PENDING) {
            throw new Exception("Reward is already " . $reward->status);
        }

        $reward->approve($userId);

        return $reward;
    }

    /**
     * @inheritDoc
     */
    public function rejectReward(int $id, int $userId, string $reason): EmployeeReward
    {
        $reward = $this->getRewardById($id);

        if (!$reward) {
            throw new Exception("Employee reward not found.");
        }

        if ($reward->status !== EmployeeReward::STATUS_PENDING) {
            throw new Exception("Reward is already " . $reward->status);
        }

        $reward->reject($userId, $reason);

        return $reward;
    }

    /**
     * @inheritDoc
     */
    public function deleteReward(int $id): bool
    {
        $reward = EmployeeReward::find($id);

        if (!$reward) {
            throw new Exception("Employee reward not found.");
        }

        if ($reward->status !== EmployeeReward::STATUS_PENDING) {
            throw new Exception("Only pending rewards can be deleted.");
        }

        return $reward->delete();
    }
}
