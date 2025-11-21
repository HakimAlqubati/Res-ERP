<?php

namespace App\Services\Financial\Filters;

use Illuminate\Database\Eloquent\Builder;

class FinancialCategoryReportFilter
{
    protected ?string $startDate = null;
    protected ?string $endDate = null;
    protected ?string $type = null;
    protected ?array $categoryIds = null;
    protected ?int $branchId = null;
    protected ?array $branchIds = null;
    protected ?string $status = null;
    protected ?bool $isSystem = null;
    protected ?bool $isVisible = null;
    protected ?float $minAmount = null;
    protected ?float $maxAmount = null;
    protected ?int $paymentMethodId = null;

    public function __construct(array $filters = [])
    {
        $this->startDate = $filters['start_date'] ?? null;
        $this->endDate = $filters['end_date'] ?? null;
        $this->type = $filters['type'] ?? null;
        $this->categoryIds = $filters['category_ids'] ?? null;
        $this->branchId = $filters['branch_id'] ?? null;
        $this->branchIds = $filters['branch_ids'] ?? null;
        $this->status = $filters['status'] ?? null;
        $this->isSystem = isset($filters['is_system']) ? (bool) $filters['is_system'] : null;
        $this->isVisible = isset($filters['is_visible']) ? (bool) $filters['is_visible'] : null;
        $this->minAmount = $filters['min_amount'] ?? null;
        $this->maxAmount = $filters['max_amount'] ?? null;
        $this->paymentMethodId = $filters['payment_method_id'] ?? null;
    }

    public function applyToTransactionQuery(Builder $query): Builder
    {
        // Date range filter
        if ($this->startDate) {
            $query->whereDate('transaction_date', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $query->whereDate('transaction_date', '<=', $this->endDate);
        }

        // Type filter
        if ($this->type) {
            $query->where('type', $this->type);
        }

        // Category filter
        if ($this->categoryIds && is_array($this->categoryIds)) {
            $query->whereIn('category_id', $this->categoryIds);
        }

        // Branch filter
        if ($this->branchId) {
            $query->where('branch_id', $this->branchId);
        }

        if ($this->branchIds && is_array($this->branchIds)) {
            $query->whereIn('branch_id', $this->branchIds);
        }

        // Status filter
        if ($this->status) {
            $query->where('status', $this->status);
        }

        // Amount range filter
        if ($this->minAmount !== null) {
            $query->where('amount', '>=', $this->minAmount);
        }

        if ($this->maxAmount !== null) {
            $query->where('amount', '<=', $this->maxAmount);
        }

        // Payment method filter
        if ($this->paymentMethodId) {
            $query->where('payment_method_id', $this->paymentMethodId);
        }

        return $query;
    }

    public function applyToCategoryQuery(Builder $query): Builder
    {
        // Type filter
        if ($this->type) {
            $query->where('type', $this->type);
        }

        // Category IDs filter
        if ($this->categoryIds && is_array($this->categoryIds)) {
            $query->whereIn('id', $this->categoryIds);
        }

        // System category filter
        if ($this->isSystem !== null) {
            $query->where('is_system', $this->isSystem);
        }

        // Visible category filter
        if ($this->isVisible !== null) {
            $query->where('is_visible', $this->isVisible);
        }

        return $query;
    }

    // Getters
    public function getStartDate(): ?string
    {
        return $this->startDate;
    }

    public function getEndDate(): ?string
    {
        return $this->endDate;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getCategoryIds(): ?array
    {
        return $this->categoryIds;
    }

    public function getBranchId(): ?int
    {
        return $this->branchId;
    }

    public function getBranchIds(): ?array
    {
        return $this->branchIds;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function getIsSystem(): ?bool
    {
        return $this->isSystem;
    }

    public function getIsVisible(): ?bool
    {
        return $this->isVisible;
    }

    public function getMinAmount(): ?float
    {
        return $this->minAmount;
    }

    public function getMaxAmount(): ?float
    {
        return $this->maxAmount;
    }

    public function getPaymentMethodId(): ?int
    {
        return $this->paymentMethodId;
    }

    public function toArray(): array
    {
        return array_filter([
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'type' => $this->type,
            'category_ids' => $this->categoryIds,
            'branch_id' => $this->branchId,
            'branch_ids' => $this->branchIds,
            'status' => $this->status,
            'is_system' => $this->isSystem,
            'is_visible' => $this->isVisible,
            'min_amount' => $this->minAmount,
            'max_amount' => $this->maxAmount,
            'payment_method_id' => $this->paymentMethodId,
        ], fn($value) => $value !== null);
    }
}
