<?php

namespace App\DTOs\HR\Dashboard;

use Illuminate\Http\Request;

class DashboardFilterDTO
{
    public function __construct(
        public readonly ?int $branchId,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            branchId: $request->input('branch_id') ? (int) $request->input('branch_id') : null,
        );
    }
}   
