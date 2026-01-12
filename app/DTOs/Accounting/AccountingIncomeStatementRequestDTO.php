<?php

namespace App\DTOs\Accounting;

class AccountingIncomeStatementRequestDTO
{
    public function __construct(
        public string $startDate,
        public string $endDate,
        public ?int $branchId = null,
    ) {}
}
