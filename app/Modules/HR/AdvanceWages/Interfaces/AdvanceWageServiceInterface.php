<?php

namespace App\Modules\HR\AdvanceWages\Interfaces;

use App\Models\AdvanceWage;
use Illuminate\Pagination\LengthAwarePaginator;

interface AdvanceWageServiceInterface
{
    /**
     * Get paginated advance wages with optional filters.
     */
    public function getAll(array $filters, int $perPage = 15): LengthAwarePaginator;

    /**
     * Find a single advance wage by ID.
     */
    public function findById(int $id): AdvanceWage;

    /**
     * Create a new advance wage record.
     */
    public function create(array $data): AdvanceWage;

    /**
     * Update an existing advance wage.
     */
    public function update(AdvanceWage $advanceWage, array $data): AdvanceWage;

    /**
     * Delete an advance wage.
     */
    public function delete(AdvanceWage $advanceWage): void;

    /**
     * Approve an advance wage.
     */
    public function approve(AdvanceWage $advanceWage): AdvanceWage;

    /**
     * Cancel an advance wage.
     */
    public function cancel(AdvanceWage $advanceWage): AdvanceWage;
}
