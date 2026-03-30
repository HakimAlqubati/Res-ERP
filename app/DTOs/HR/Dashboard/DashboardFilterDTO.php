<?php

namespace App\DTOs\HR\Dashboard;

use Illuminate\Http\Request;

class DashboardFilterDTO
{
    public function __construct(
        public readonly ?int $branchId,
        public readonly ?\Carbon\Carbon $dateTime,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            branchId: $request->filled('branch_id') ? (int) $request->input('branch_id') : null,
            dateTime: $request->filled('date_time') ? \Carbon\Carbon::parse($request->input('date_time')) : null,
        );
    }
}   
