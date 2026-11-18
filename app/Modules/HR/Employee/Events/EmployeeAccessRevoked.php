<?php

declare(strict_types=1);

namespace App\Modules\HR\Employee\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class EmployeeAccessRevoked
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $userId,
        public readonly int $employeeId,
        public readonly string $reason = '',
    ) {}
}
