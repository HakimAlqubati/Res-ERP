<?php

declare(strict_types=1);

namespace App\Modules\HR\EmployeeDocuments\DTOs;

final readonly class DocumentSummaryDTO
{
    public function __construct(
        public int $total,
        public int $expired,
        public int $expiringSoon,
        public int $valid,
        public int $noExpiry,
    ) {}

    public function attentionRequiredCount(): int
    {
        return $this->expired + $this->expiringSoon;
    }

    public function hasAttentionRequired(): bool
    {
        return $this->attentionRequiredCount() > 0;
    }
}
