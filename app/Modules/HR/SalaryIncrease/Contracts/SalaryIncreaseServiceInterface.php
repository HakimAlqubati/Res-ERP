<?php

declare(strict_types=1);

namespace App\Modules\HR\SalaryIncrease\Contracts;

use App\Models\SalaryIncreaseRequest;
use App\Modules\HR\SalaryIncrease\DTOs\SalaryIncreaseDataDTO;
use App\Modules\HR\SalaryIncrease\DTOs\SalaryIncreaseFilterDTO;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface SalaryIncreaseServiceInterface
{
    public function list(SalaryIncreaseFilterDTO $filter, int $perPage = 15): LengthAwarePaginator;

    public function getById(int $id): SalaryIncreaseRequest;

    public function create(SalaryIncreaseDataDTO $data): SalaryIncreaseRequest;

    public function update(int $id, SalaryIncreaseDataDTO $data): SalaryIncreaseRequest;

    public function approve(int $id, ?int $approverId = null): SalaryIncreaseRequest;

    public function reject(int $id, string $rejectionReason, ?int $rejectorId = null): SalaryIncreaseRequest;

    public function delete(int $id): bool;
}
