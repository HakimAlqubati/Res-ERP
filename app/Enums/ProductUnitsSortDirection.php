<?php

namespace App\Enums;

use App\Models\Setting;

enum ProductUnitsSortDirection: string
{
    case ASC = 'asc';
    case DESC = 'desc';

    public const DEFAULT = self::ASC->value;
    public const SETTING_KEY = 'product_units_sort_direction';

    public function label(): string
    {
        return match ($this) {
            self::ASC => 'Smallest to Largest (Ascending)',
            self::DESC => 'Largest to Smallest (Descending)',
        };
    }

    public static function options(): array
    {
        return [
            self::ASC->value => self::ASC->label(),
            self::DESC->value => self::DESC->label(),
        ];
    }

    public static function current(): self
    {
        $value = Setting::getSetting(self::SETTING_KEY, self::DEFAULT);

        return self::tryFrom($value) ?? self::ASC;
    }

    public static function isAsc(): bool
    {
        return self::current() === self::ASC;
    }

    public static function isDesc(): bool
    {
        return self::current() === self::DESC;
    }
}
