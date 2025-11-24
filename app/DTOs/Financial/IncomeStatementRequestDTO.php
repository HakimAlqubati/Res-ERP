<?php

namespace App\DTOs\Financial;

use Illuminate\Http\Request;

class IncomeStatementRequestDTO
{
    public function __construct(
        public readonly ?string $startDate,
        public readonly ?string $endDate,
        public readonly ?int $branchId,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            startDate: $request->input('start_date'),
            endDate: $request->input('end_date'),
            branchId: $request->input('branch_id'),
        );
    }

    public function toArray(): array
    {
        return [
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'branch_id' => $this->branchId,
        ];
    }
}
