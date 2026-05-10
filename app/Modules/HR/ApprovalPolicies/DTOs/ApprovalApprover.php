<?php

namespace App\Modules\HR\ApprovalPolicies\DTOs;

final readonly class ApprovalApprover
{
    public function __construct(
        public int $userId,
        public ?int $employeeId = null,
    ) {
    }
}
