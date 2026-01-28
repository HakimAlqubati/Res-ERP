<?php

namespace App\DTOs\HR\ImageRecognize;

use App\Models\Employee;

class EmployeeMatch
{
    public function __construct(
        public bool $found,
        public ?string $name,
        public ?string $employeeId,
        public ?Employee $employeeData,
        public ?float $similarity = null,
        public ?float $confidence = null,
        public ?string $message = null,
    ) {}

    public static function notFound(?string $message = 'No match found'): self
    {
        return new self(false, 'No match found', null, null, null, null, $message);
    }
}
