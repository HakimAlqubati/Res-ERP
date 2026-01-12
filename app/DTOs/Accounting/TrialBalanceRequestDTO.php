<?php

namespace App\DTOs\Accounting;

class TrialBalanceRequestDTO
{
    public function __construct(
        public string $startDate,
        public string $endDate,
        public ?string $accountType = null,
        public bool $showZeroBalances = false,
    ) {}
}
