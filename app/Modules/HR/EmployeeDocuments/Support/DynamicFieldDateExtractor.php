<?php

declare(strict_types=1);

namespace App\Modules\HR\EmployeeDocuments\Support;

use App\Casts\SafeJsonArrayCast;
use Carbon\CarbonImmutable;

/**
 * Extracts issue_date and expiry_date candidates from legacy dynamic_field_values JSON payloads
 * without mutating or deleting the original JSON structure.
 */
final class DynamicFieldDateExtractor
{
    /**
     * Exact normalized keys that unambiguously identify an expiry date.
     *
     * @var array<int, string>
     */
    private const EXPIRY_EXACT_KEYS = [
        'expiry_date',
        'expire_date',
        'expiration_date',
        'end_date',
        'valid_until',
        'valid_to',
        'valid_till',
        'exp_date',
        'expiry',
        'expires_at',
        'expired_at',
        'date_of_expiry',
        'date_of_expiration',
    ];

    /**
     * Exact normalized keys that unambiguously identify an issue date.
     *
     * @var array<int, string>
     */
    private const ISSUE_EXACT_KEYS = [
        'issue_date',
        'issued_date',
        'issued_at',
        'start_date',
        'valid_from',
        'date_of_issue',
        'issuance_date',
    ];

    /**
     * Substring tokens for fallback matching on date-typed field names.
     *
     * @var array<int, string>
     */
    private const EXPIRY_TOKENS = [
        'expir',
        'end_date',
        'valid_until',
        'valid_to',
        'valid_till',
        'exp_date',
    ];

    /**
     * @var array<int, string>
     */
    private const ISSUE_TOKENS = [
        'issue',
        'start_date',
        'valid_from',
    ];

    /**
     * @param  array<int, array{field_name: string, field_type: string}>  $fieldDefinitions
     * @return array{issue_date: ?string, expiry_date: ?string, unmapped_date_keys: array<int, string>}
     */
    public function extract(mixed $rawDynamicValues, array $fieldDefinitions = []): array
    {
        $values = SafeJsonArrayCast::normalize($rawDynamicValues);

        if ($values === null || $values === []) {
            return [
                'issue_date' => null,
                'expiry_date' => null,
                'unmapped_date_keys' => [],
            ];
        }

        $dateFieldNames = [];
        foreach ($fieldDefinitions as $def) {
            if (($def['field_type'] ?? null) === 'date' && isset($def['field_name'])) {
                $dateFieldNames[$this->normalizeKey((string) $def['field_name'])] = true;
            }
        }

        $issueDate = null;
        $expiryDate = null;
        $unmappedDateKeys = [];

        foreach ($values as $key => $rawValue) {
            if (! is_string($key) || ! is_scalar($rawValue)) {
                continue;
            }

            $normalizedKey = $this->normalizeKey($key);
            $parsedDate = $this->parseDate((string) $rawValue);

            if ($parsedDate === null) {
                continue;
            }

            if ($expiryDate === null && $this->isExpiryKey($normalizedKey)) {
                $expiryDate = $parsedDate;
                continue;
            }

            if ($issueDate === null && $this->isIssueKey($normalizedKey)) {
                $issueDate = $parsedDate;
                continue;
            }

            if (isset($dateFieldNames[$normalizedKey])) {
                $unmappedDateKeys[] = $key;
            }
        }

        return [
            'issue_date' => $issueDate,
            'expiry_date' => $expiryDate,
            'unmapped_date_keys' => $unmappedDateKeys,
        ];
    }

    public function isMappedDateKey(string $key): bool
    {
        $normalized = $this->normalizeKey($key);

        return $this->isExpiryKey($normalized) || $this->isIssueKey($normalized);
    }

    private function normalizeKey(string $key): string
    {
        $key = strtolower(trim($key));
        $key = preg_replace('/[\s\-]+/', '_', $key) ?? $key;

        return trim($key, '_');
    }

    private function isExpiryKey(string $normalizedKey): bool
    {
        if (in_array($normalizedKey, self::EXPIRY_EXACT_KEYS, true)) {
            return true;
        }

        foreach (self::EXPIRY_TOKENS as $token) {
            if (str_contains($normalizedKey, $token)) {
                return true;
            }
        }

        return false;
    }

    private function isIssueKey(string $normalizedKey): bool
    {
        if (in_array($normalizedKey, self::ISSUE_EXACT_KEYS, true)) {
            return true;
        }

        foreach (self::ISSUE_TOKENS as $token) {
            if (str_contains($normalizedKey, $token)) {
                return true;
            }
        }

        return false;
    }

    private function parseDate(string $value): ?string
    {
        $trimmed = trim($value);

        if ($trimmed === '' || is_numeric($trimmed)) {
            return null;
        }

        // Guard against arbitrary prose strings being misparsed as dates.
        if (! preg_match('/^\d{4}[-\/]\d{1,2}[-\/]\d{1,2}(\s+\d{2}:\d{2}(:\d{2})?)?$|^\d{1,2}[-\/]\d{1,2}[-\/]\d{4}$/', $trimmed)) {
            return null;
        }

        try {
            $parsed = CarbonImmutable::parse($trimmed);

            if ($parsed->year < 1950 || $parsed->year > 2150) {
                return null;
            }

            return $parsed->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
