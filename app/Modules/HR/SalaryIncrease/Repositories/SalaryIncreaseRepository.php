<?php

declare(strict_types=1);

namespace App\Modules\HR\SalaryIncrease\Repositories;

use App\Models\SalaryIncreaseRequest;
use App\Modules\HR\SalaryIncrease\Builders\SalaryIncreaseQueryBuilder;
use App\Modules\HR\SalaryIncrease\Contracts\SalaryIncreaseRepositoryInterface;
use App\Modules\HR\SalaryIncrease\DTOs\SalaryIncreaseFilterDTO;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class SalaryIncreaseRepository implements SalaryIncreaseRepositoryInterface
{
    public function query(SalaryIncreaseFilterDTO $filter): Builder
    {
        return SalaryIncreaseQueryBuilder::make()
            ->withStandardRelations()
            ->applyFilter($filter)
            ->orderedForReview()
            ->build();
    }

    public function paginate(SalaryIncreaseFilterDTO $filter, int $perPage = 15): LengthAwarePaginator
    {
        return $this->query($filter)->paginate($perPage);
    }

    public function findById(int $id): ?SalaryIncreaseRequest
    {
        return SalaryIncreaseQueryBuilder::make()
            ->withStandardRelations()
            ->build()
            ->find($id);
    }

    public function findByIdOrFail(int $id): SalaryIncreaseRequest
    {
        return SalaryIncreaseQueryBuilder::make()
            ->withStandardRelations()
            ->build()
            ->findOrFail($id);
    }

    public function findByIdForUpdate(int $id): SalaryIncreaseRequest
    {
        return SalaryIncreaseRequest::query()
            ->lockForUpdate()
            ->findOrFail($id);
    }

    public function create(array $attributes): SalaryIncreaseRequest
    {
        return SalaryIncreaseRequest::create($attributes);
    }

    public function update(SalaryIncreaseRequest $request, array $attributes): SalaryIncreaseRequest
    {
        $request->update($attributes);

        return $request->refresh();
    }

    public function delete(SalaryIncreaseRequest $request): bool
    {
        return (bool) $request->delete();
    }

    public function hasPendingForEmployee(int $employeeId, ?int $ignoreRequestId = null): bool
    {
        return SalaryIncreaseRequest::query()
            ->where('employee_id', $employeeId)
            ->pending()
            ->when($ignoreRequestId, fn (Builder $q, int $id) => $q->where('id', '!=', $id))
            ->exists();
    }
}
