<?php

namespace App\Services\HR\Rewards\Contracts;

use App\Models\EmployeeReward;
use Illuminate\Pagination\LengthAwarePaginator;

interface EmployeeRewardServiceInterface
{
    /**
     * Retrieve a paginated list of rewards based on filters.
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getRewardsList(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Create a new employee reward.
     *
     * @param array $data
     * @return EmployeeReward
     */
    public function createReward(array $data): EmployeeReward;

    /**
     * Update an existing employee reward.
     *
     * @param int $id
     * @param array $data
     * @return EmployeeReward
     */
    public function updateReward(int $id, array $data): EmployeeReward;

    /**
     * Retrieve a reward by its ID.
     *
     * @param int $id
     * @return EmployeeReward|null
     */
    public function getRewardById(int $id): ?EmployeeReward;

    /**
     * Approve a reward.
     *
     * @param int $id
     * @param int $userId
     * @return EmployeeReward
     */
    public function approveReward(int $id, int $userId): EmployeeReward;

    /**
     * Reject a reward.
     *
     * @param int $id
     * @param int $userId
     * @param string $reason
     * @return EmployeeReward
     */
    public function rejectReward(int $id, int $userId, string $reason): EmployeeReward;

    /**
     * Delete a reward.
     *
     * @param int $id
     * @return bool
     */
    public function deleteReward(int $id): bool;
}
