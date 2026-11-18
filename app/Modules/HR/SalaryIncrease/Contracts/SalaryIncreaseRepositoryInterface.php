<?php

declare(strict_types=1);

namespace App\Modules\HR\SalaryIncrease\Contracts;

use App\Models\SalaryIncreaseRequest;
use App\Modules\HR\SalaryIncrease\DTOs\SalaryIncreaseFilterDTO;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

interface SalaryIncreaseRepositoryInterface
{
    public function query(SalaryIncreaseFilterDTO $filter): Builder;

    public function paginate(SalaryIncreaseFilterDTO $filter, int $perPage = 15): LengthAwarePaginator;

    public function findById(int $id): ?SalaryIncreaseRequest;

    public function findByIdOrFail(int $id): SalaryIncreaseRequest;

    public function findByIdForUpdate(int $id): SalaryIncreaseRequest;

    public function create(array $attributes): SalaryIncreaseRequest;

    public function update(SalaryIncreaseRequest $request, array $attributes): SalaryIncreaseRequest;

    public function delete(SalaryIncreaseRequest $request): bool;

    public function hasPendingForEmployee(int $employeeId, ?int $ignoreRequestId = null): bool;
}
