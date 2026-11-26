<?php

declare(strict_types=1);

namespace App\Modules\HR\SalaryIncrease\Builders;

use App\Models\EmployeeSalaryLog;
use Carbon\Carbon;
use InvalidArgumentException;

/**
 * Fluent Builder for constructing canonical `hr_employee_salary_logs` persistence arrays.
 * Centralizes `effective_from` month normalization, decimal rounding, and `change_amount` calculation.
 */
final class EmployeeSalaryLogPayloadBuilder
{
    private ?int $employeeId = null;

    private ?float $oldSalary = null;

    private float $newSalary = 0.0;

    private ?Carbon $effectiveFrom = null;

    private ?string $effectiveDate = null;

    private string $source = EmployeeSalaryLog::SOURCE_DIRECT_EDIT;

    private ?int $requestId = null;

    private ?string $notes = null;

    private ?int $createdBy = null;

    public static function forEmployee(int $employeeId): self
    {
        $builder = new self;
        $builder->employeeId = $employeeId;

        return $builder;
    }

    public function fromOldSalary(?float $oldSalary): self
    {
        $clone = clone $this;
        $clone->oldSalary = $oldSalary !== null ? round($oldSalary, 2) : null;

        return $clone;
    }

    public function toNewSalary(float $newSalary): self
    {
        $clone = clone $this;
        $clone->newSalary = round($newSalary, 2);

        return $clone;
    }

    public function effectiveOn(Carbon|string $effectiveDate, Carbon|string|null $effectiveFromOverride = null): self
    {
        $parsedDate = $effectiveDate instanceof Carbon ? $effectiveDate->copy() : Carbon::parse($effectiveDate);
        $normalizedFrom = $effectiveFromOverride !== null
            ? EmployeeSalaryLog::normalizeEffectiveFrom($effectiveFromOverride)
            : $parsedDate->copy()->startOfMonth();

        $clone = clone $this;
        $clone->effectiveFrom = $normalizedFrom;
        $clone->effectiveDate = $parsedDate->toDateString();

        return $clone;
    }

    public function fromSource(string $source): self
    {
        $clone = clone $this;
        $clone->source = $source;

        return $clone;
    }

    public function linkedToRequest(?int $requestId): self
    {
        $clone = clone $this;
        $clone->requestId = $requestId;

        return $clone;
    }

    public function withNotes(?string $notes): self
    {
        $clone = clone $this;
        $clone->notes = $notes;

        return $clone;
    }

    public function byActor(?int $actorId): self
    {
        $clone = clone $this;
        $clone->createdBy = $actorId;

        return $clone;
    }

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        if (! $this->employeeId || ! $this->effectiveFrom) {
            throw new InvalidArgumentException('Employee ID and effective date are required to build a salary log payload.');
        }

        $changeAmount = $this->oldSalary !== null
            ? round($this->newSalary - $this->oldSalary, 2)
            : null;

        return [
            'employee_id' => $this->employeeId,
            'old_salary' => $this->oldSalary,
            'new_salary' => $this->newSalary,
            'change_amount' => $changeAmount,
            'effective_from' => $this->effectiveFrom->toDateString(),
            'effective_date' => $this->effectiveDate,
            'source' => $this->source,
            'salary_increase_request_id' => $this->requestId,
            'notes' => $this->notes,
            'created_by' => $this->createdBy,
        ];
    }
}
