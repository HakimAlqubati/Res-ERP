<?php

namespace App\DTOs\Financial;

class CategoryTransactionSummaryDTO
{
    public function __construct(
        public readonly int $categoryId,
        public readonly string $categoryName,
        public readonly string $categoryType,
        public readonly float $totalAmount,
        public readonly int $transactionCount,
        public readonly float $averageAmount,
        public readonly array $statusBreakdown,
        public readonly array $branchBreakdown,
        public readonly ?float $minAmount = null,
        public readonly ?float $maxAmount = null,
    ) {}

    public function toArray(): array
    {
        return [
            'category_id' => $this->categoryId,
            'category_name' => $this->categoryName,
            'category_type' => $this->categoryType,
            'total_amount' => round($this->totalAmount, 2),
            'transaction_count' => $this->transactionCount,
            'average_amount' => round($this->averageAmount, 2),
            'min_amount' => $this->minAmount ? round($this->minAmount, 2) : null,
            'max_amount' => $this->maxAmount ? round($this->maxAmount, 2) : null,
            'status_breakdown' => $this->statusBreakdown,
            'branch_breakdown' => $this->branchBreakdown,
        ];
    }
}
