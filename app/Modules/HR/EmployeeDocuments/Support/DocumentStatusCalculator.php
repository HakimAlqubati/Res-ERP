<?php

declare(strict_types=1);

namespace App\Modules\HR\EmployeeDocuments\Support;

use App\Modules\HR\EmployeeDocuments\Enums\DocumentExpiryStatus;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Single source of truth for computing document expiry status and remaining days in PHP.
 * Mirrors the exact SQL predicates in EmployeeDocumentQueryBuilder.
 */
final class DocumentStatusCalculator
{
    public const DEFAULT_ALERT_DAYS = 30;

    public function resolveStatus(
        bool $hasExpiry,
        CarbonInterface|string|null $expiryDate,
        ?int $alertDays = null,
        ?CarbonInterface $asOf = null,
    ): DocumentExpiryStatus {
        if (! $hasExpiry) {
            return DocumentExpiryStatus::NoExpiry;
        }

        $expiry = $this->normalizeDate($expiryDate);

        if ($expiry === null) {
            return DocumentExpiryStatus::NoExpiry;
        }

        $today = $this->normalizeReferenceDate($asOf);
        $threshold = $today->addDays($this->effectiveAlertDays($alertDays));

        if ($expiry->lt($today)) {
            return DocumentExpiryStatus::Expired;
        }

        if ($expiry->lte($threshold)) {
            return DocumentExpiryStatus::ExpiringSoon;
        }

        return DocumentExpiryStatus::Valid;
    }

    public function remainingDays(
        bool $hasExpiry,
        CarbonInterface|string|null $expiryDate,
        ?CarbonInterface $asOf = null,
    ): ?int {
        if (! $hasExpiry) {
            return null;
        }

        $expiry = $this->normalizeDate($expiryDate);

        if ($expiry === null) {
            return null;
        }

        $today = $this->normalizeReferenceDate($asOf);

        return (int) $today->diffInDays($expiry, false);
    }

    public function effectiveAlertDays(?int $alertDays): int
    {
        if ($alertDays === null || $alertDays < 0) {
            return self::DEFAULT_ALERT_DAYS;
        }

        return $alertDays;
    }

    private function normalizeDate(CarbonInterface|string|null $date): ?CarbonImmutable
    {
        if ($date === null || $date === '') {
            return null;
        }

        if ($date instanceof CarbonInterface) {
            return CarbonImmutable::instance($date)->startOfDay();
        }

        try {
            return CarbonImmutable::parse($date)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeReferenceDate(?CarbonInterface $asOf): CarbonImmutable
    {
        return $asOf
            ? CarbonImmutable::instance($asOf)->startOfDay()
            : CarbonImmutable::today();
    }
}
