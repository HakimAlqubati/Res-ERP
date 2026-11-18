<?php

declare(strict_types=1);

namespace App\Modules\HR\EmployeeApplications\DTOs;

use App\Models\Employee;

final class ApplicationBranchContext
{
    public function __construct(
        public readonly Employee $employee,
        public readonly int $applicationTypeId,
        public readonly array $payload = [],
        public readonly ?int $explicitBranchId = null,
    ) {}
}
