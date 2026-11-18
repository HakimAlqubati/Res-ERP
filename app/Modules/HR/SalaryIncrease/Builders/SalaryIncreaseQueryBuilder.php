<?php

declare(strict_types=1);

namespace App\Modules\HR\SalaryIncrease\Builders;

use App\Models\SalaryIncreaseRequest;
use App\Modules\HR\SalaryIncrease\DTOs\SalaryIncreaseFilterDTO;
use Illuminate\Database\Eloquent\Builder;

/**
 * Single query builder for SalaryIncreaseRequest shared across
 * Repositories, API Controllers, and Filament Resources.
 */
final class SalaryIncreaseQueryBuilder
{
    private Builder $query;

    public function __construct(?Builder $baseQuery = null)
    {
        $this->query = $baseQuery ?? SalaryIncreaseRequest::query();
    }

    public static function make(?Builder $baseQuery = null): self
    {
        return new self($baseQuery);
    }

    public function withStandardRelations(): self
    {
        $this->query->with([
            'employee:id,name,employee_no,branch_id,salary,active',
            'branch:id,name',
            'createdBy:id,name',
            'approvedBy:id,name',
            'rejectedBy:id,name',
        ]);

        return $this;
    }

    public function applyAccessScopes(bool $apply = true): self
    {
        if ($apply) {
            $this->query->forBranchManager()->forEmployee();
        }

        return $this;
    }

    public function applyFilter(SalaryIncreaseFilterDTO $filter): self
    {
        $this->applyAccessScopes($filter->applyScopes);

        $this->query
            ->when($filter->employeeId, fn (Builder $q, int $employeeId) => $q->where('employee_id', $employeeId))
            ->when($filter->branchId, fn (Builder $q, int $branchId) => $q->where('branch_id', $branchId))
            ->when($filter->status, fn (Builder $q, string $status) => $q->where('status', $status))
            ->when($filter->dateFrom, fn (Builder $q, string $from) => $q->whereDate('created_at', '>=', $from))
            ->when($filter->dateTo, fn (Builder $q, string $to) => $q->whereDate('created_at', '<=', $to))
            ->when($filter->search, function (Builder $q, string $search): void {
                $q->where(function (Builder $sub) use ($search): void {
                    $sub->where('notes', 'like', "%{$search}%")
                        ->orWhereHas('employee', function (Builder $empQuery) use ($search): void {
                            $empQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('employee_no', 'like', "%{$search}%");
                        });
                });
            });

        return $this;
    }

    public function orderedForReview(): self
    {
        $this->query
            ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [SalaryIncreaseRequest::STATUS_PENDING])
            ->latest('id');

        return $this;
    }

    public function build(): Builder
    {
        return $this->query;
    }
}
