<?php

declare(strict_types=1);

namespace App\Modules\HR\EmployeeDocuments\DTOs;

use App\Modules\HR\EmployeeDocuments\Enums\DocumentExpiryStatus;
use Carbon\CarbonImmutable;

final readonly class DocumentReportFilterDTO
{
    /**
     * @param  array<int, int>|null  $branchIds
     * @param  array<int, int>|null  $fileTypeIds
     * @param  array<int, string>|null  $nationalities
     * @param  array<int, DocumentExpiryStatus>|null  $statuses
     */
    public function __construct(
        public ?array $branchIds = null,
        public ?int $employeeId = null,
        public ?array $fileTypeIds = null,
        public ?array $nationalities = null,
        public ?DocumentExpiryStatus $status = null,
        public ?array $statuses = null,
        public ?string $expiryFrom = null,
        public ?string $expiryTo = null,
        public ?bool $onlyActiveEmployees = true,
        public ?bool $onlyActiveFiles = true,
        public bool $applyAccessScope = true,
        public ?CarbonImmutable $asOf = null,
        public ?string $search = null,
    ) {}

    public function referenceDateString(): string
    {
        return ($this->asOf ?? CarbonImmutable::today())->toDateString();
    }
}
