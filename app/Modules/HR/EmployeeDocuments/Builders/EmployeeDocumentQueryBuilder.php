<?php

declare(strict_types=1);

namespace App\Modules\HR\EmployeeDocuments\Builders;

use App\Models\EmployeeFile;
use App\Models\User;
use App\Modules\HR\EmployeeDocuments\DTOs\DocumentReportFilterDTO;
use App\Modules\HR\EmployeeDocuments\Enums\DocumentExpiryStatus;
use App\Modules\HR\EmployeeDocuments\Notifications\DocumentExpiryRecipientResolver;
use App\Modules\HR\EmployeeDocuments\Support\DocumentStatusCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/**
 * Single source of truth for querying employee documents across
 * Filament Reports, Excel Exports, Notifications, and future APIs.
 */
final class EmployeeDocumentQueryBuilder
{
    private Builder $query;

    private bool $joined = false;

    public function __construct(?Builder $baseQuery = null)
    {
        $this->query = $baseQuery ?? EmployeeFile::query();
    }

    public static function make(?Builder $baseQuery = null): self
    {
        return new self($baseQuery);
    }

    public function joinTables(): self
    {
        if ($this->joined) {
            return $this;
        }

        $this->query
            ->join('hr_employee_file_types as ft', function ($join): void {
                $join->on('ft.id', '=', 'hr_employee_files.file_type_id')
                    ->whereNull('ft.deleted_at');
            })
            ->join('hr_employees as emp', function ($join): void {
                $join->on('emp.id', '=', 'hr_employee_files.employee_id')
                    ->whereNull('emp.deleted_at');
            })
            ->leftJoin('branches as br', 'br.id', '=', 'emp.branch_id');

        $this->joined = true;

        return $this;
    }

    public function withReportSelect(?CarbonImmutable $asOf = null): self
    {
        $this->joinTables();

        $referenceDate = ($asOf ?? CarbonImmutable::today())->toDateString();

        $this->query->select([
            'hr_employee_files.id',
            'hr_employee_files.employee_id',
            'hr_employee_files.file_type_id',
            'hr_employee_files.attachment',
            'hr_employee_files.issue_date',
            'hr_employee_files.expiry_date',
            'hr_employee_files.active',
            'hr_employee_files.description',
            'hr_employee_files.dynamic_field_values',
            'hr_employee_files.created_at',
            'hr_employee_files.updated_at',
            'emp.name as employee_name',
            'emp.employee_no as employee_no',
            'emp.nationality as employee_nationality',
            'emp.branch_id as employee_branch_id',
            'emp.active as employee_active',
            'br.name as branch_name',
            'ft.name as file_type_name',
            'ft.has_expiry as file_type_has_expiry',
            'ft.expiry_alert_days as file_type_alert_days',
            'ft.is_required as file_type_is_required',
        ])->selectRaw(
            'CASE WHEN ft.has_expiry = 1 AND hr_employee_files.expiry_date IS NOT NULL THEN DATEDIFF(hr_employee_files.expiry_date, ?) ELSE NULL END as remaining_days',
            [$referenceDate]
        );

        return $this;
    }

    public function applyAccessScope(?User $user = null): self
    {
        $this->joinTables();

        $actor = $user ?? auth()->user();

        if (! $actor instanceof User) {
            $this->query->whereRaw('1 = 0');

            return $this;
        }

        $actor->loadMissing('roles:id');
        $roleIds = $actor->roles->pluck('id')->map(static fn ($id): int => (int) $id)->all();

        if (array_intersect($roleIds, [
            DocumentExpiryRecipientResolver::ROLE_SUPER_ADMIN,
            DocumentExpiryRecipientResolver::ROLE_SYSTEM_MANAGER,
            DocumentExpiryRecipientResolver::ROLE_HR,
        ]) !== []) {
            return $this;
        }

        if (in_array(DocumentExpiryRecipientResolver::ROLE_BRANCH_MANAGER, $roleIds, true)) {
            $branchIds = $actor->all_branch_ids;

            if ($branchIds === []) {
                $this->query->whereRaw('1 = 0');
            } else {
                $this->query->whereIn('emp.branch_id', $branchIds);
            }

            return $this;
        }

        $employeeId = $actor->employee?->id;

        if ($employeeId) {
            $this->query->where('hr_employee_files.employee_id', $employeeId);

            return $this;
        }

        $this->query->whereRaw('1 = 0');

        return $this;
    }

    public function applyFilter(DocumentReportFilterDTO $filter, bool $includeStatus = true): self
    {
        $this->joinTables();

        if ($filter->applyAccessScope) {
            $this->applyAccessScope();
        }

        $referenceDate = $filter->referenceDateString();

        $this->query
            ->when(
                $filter->onlyActiveEmployees !== null,
                fn (Builder $q): Builder => $q->where('emp.active', $filter->onlyActiveEmployees ? 1 : 0)
            )
            ->when(
                $filter->onlyActiveFiles !== null,
                fn (Builder $q): Builder => $q->where('hr_employee_files.active', $filter->onlyActiveFiles ? 1 : 0)
            )
            ->when(
                $filter->branchIds !== null && $filter->branchIds !== [],
                fn (Builder $q): Builder => $q->whereIn('emp.branch_id', $filter->branchIds)
            )
            ->when(
                $filter->employeeId !== null,
                fn (Builder $q): Builder => $q->where('hr_employee_files.employee_id', $filter->employeeId)
            )
            ->when(
                $filter->fileTypeIds !== null && $filter->fileTypeIds !== [],
                fn (Builder $q): Builder => $q->whereIn('hr_employee_files.file_type_id', $filter->fileTypeIds)
            )
            ->when(
                $filter->nationalities !== null && $filter->nationalities !== [],
                fn (Builder $q): Builder => $q->whereIn('emp.nationality', $filter->nationalities)
            )
            ->when(
                $filter->expiryFrom !== null && $filter->expiryFrom !== '',
                fn (Builder $q): Builder => $q->where('hr_employee_files.expiry_date', '>=', $filter->expiryFrom)
            )
            ->when(
                $filter->expiryTo !== null && $filter->expiryTo !== '',
                fn (Builder $q): Builder => $q->where('hr_employee_files.expiry_date', '<=', $filter->expiryTo)
            )
            ->when(
                $filter->search !== null && trim($filter->search) !== '',
                function (Builder $q) use ($filter): void {
                    $term = '%' . trim((string) $filter->search) . '%';
                    $q->where(function (Builder $sub) use ($term): void {
                        $sub->where('emp.name', 'like', $term)
                            ->orWhere('emp.employee_no', 'like', $term)
                            ->orWhere('ft.name', 'like', $term)
                            ->orWhere('br.name', 'like', $term);
                    });
                }
            );

        if ($includeStatus) {
            if ($filter->status !== null) {
                self::applyStatusCondition($this->query, $filter->status, $referenceDate);
            } elseif ($filter->statuses !== null && $filter->statuses !== []) {
                self::applyStatusesCondition($this->query, $filter->statuses, $referenceDate);
            }
        }

        return $this;
    }

    public function orderedByUrgency(?CarbonImmutable $asOf = null): self
    {
        $this->joinTables();

        self::applyUrgencyOrder($this->query, $asOf);

        return $this;
    }

    public static function applyUrgencyOrder(Builder $query, ?CarbonImmutable $asOf = null): Builder
    {
        $referenceDate = ($asOf ?? CarbonImmutable::today())->toDateString();
        $defaultDays = DocumentStatusCalculator::DEFAULT_ALERT_DAYS;

        return $query
            ->orderByRaw(
                'CASE
                    WHEN ft.has_expiry = 1 AND hr_employee_files.expiry_date IS NOT NULL AND hr_employee_files.expiry_date < ? THEN 0
                    WHEN ft.has_expiry = 1 AND hr_employee_files.expiry_date IS NOT NULL AND hr_employee_files.expiry_date <= DATE_ADD(?, INTERVAL COALESCE(ft.expiry_alert_days, ?) DAY) THEN 1
                    WHEN ft.has_expiry = 1 AND hr_employee_files.expiry_date IS NOT NULL THEN 2
                    ELSE 3
                END ASC',
                [$referenceDate, $referenceDate, $defaultDays]
            )
            ->orderByRaw('hr_employee_files.expiry_date IS NULL ASC')
            ->orderBy('hr_employee_files.expiry_date', 'asc')
            ->orderBy('hr_employee_files.id', 'asc');
    }

    public function build(): Builder
    {
        return $this->query;
    }

    /**
     * Reusable SQL condition keeping hr_employee_files.expiry_date bare for index usage.
     */
    public static function applyStatusCondition(
        Builder $query,
        DocumentExpiryStatus $status,
        ?string $referenceDate = null,
    ): Builder {
        $ref = $referenceDate ?? CarbonImmutable::today()->toDateString();
        $defaultDays = DocumentStatusCalculator::DEFAULT_ALERT_DAYS;

        return match ($status) {
            DocumentExpiryStatus::Expired => $query
                ->where('ft.has_expiry', 1)
                ->whereNotNull('hr_employee_files.expiry_date')
                ->where('hr_employee_files.expiry_date', '<', $ref),

            DocumentExpiryStatus::ExpiringSoon => $query
                ->where('ft.has_expiry', 1)
                ->whereNotNull('hr_employee_files.expiry_date')
                ->where('hr_employee_files.expiry_date', '>=', $ref)
                ->whereRaw(
                    'hr_employee_files.expiry_date <= DATE_ADD(?, INTERVAL COALESCE(ft.expiry_alert_days, ?) DAY)',
                    [$ref, $defaultDays]
                ),

            DocumentExpiryStatus::Valid => $query
                ->where('ft.has_expiry', 1)
                ->whereNotNull('hr_employee_files.expiry_date')
                ->whereRaw(
                    'hr_employee_files.expiry_date > DATE_ADD(?, INTERVAL COALESCE(ft.expiry_alert_days, ?) DAY)',
                    [$ref, $defaultDays]
                ),

            DocumentExpiryStatus::NoExpiry => $query->where(function (Builder $sub): void {
                $sub->where('ft.has_expiry', 0)
                    ->orWhereNull('hr_employee_files.expiry_date');
            }),
        };
    }

    /**
     * @param  array<int, DocumentExpiryStatus>  $statuses
     */
    public static function applyStatusesCondition(
        Builder $query,
        array $statuses,
        ?string $referenceDate = null,
    ): Builder {
        if ($statuses === []) {
            return $query;
        }

        $ref = $referenceDate ?? CarbonImmutable::today()->toDateString();

        return $query->where(function (Builder $group) use ($statuses, $ref): void {
            foreach ($statuses as $status) {
                $group->orWhere(function (Builder $branch) use ($status, $ref): void {
                    self::applyStatusCondition($branch, $status, $ref);
                });
            }
        });
    }
}
