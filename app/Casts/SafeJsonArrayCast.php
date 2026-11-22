<?php

declare(strict_types=1);

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Safely decodes JSON columns that may contain either standard JSON objects/arrays
 * or legacy double-encoded JSON strings, and always persists clean single-encoded JSON.
 */
final class SafeJsonArrayCast implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     * @return array<mixed>|null
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?array
    {
        return self::normalize($value);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = self::normalize($value);

        if ($normalized === null) {
            return null;
        }

        $encoded = json_encode($normalized, JSON_UNESCAPED_UNICODE);

        return $encoded === false ? null : $encoded;
    }

    /**
     * Normalize an array or (single/double)-encoded JSON string into an array.
     *
     * @return array<mixed>|null
     */
    public static function normalize(mixed $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value)) {
            return null;
        }

        $current = $value;

        // Safely unwrap up to 3 layers in case of legacy double-encoded JSON strings.
        for ($depth = 0; $depth < 3 && is_string($current); $depth++) {
            $decoded = json_decode($current, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return null;
            }

            $current = $decoded;
        }

        return is_array($current) ? $current : null;
    }
}
