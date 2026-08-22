<?php

namespace App\Enums;

enum ProductCodeGenerationMethod: string
{
    case AUTO = 'auto';
    case MANUAL = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::AUTO => 'Automatically based on Category Prefix',
            self::MANUAL => 'Manual Entry',
        };
    }

    public static function options(): array
    {
        return [
            self::AUTO->value => self::AUTO->label(),
            self::MANUAL->value => self::MANUAL->label(),
        ];
    }

    public static function current(): string
    {
        return \App\Models\Setting::getSetting('product_code_generation_method', self::AUTO->value);
    }

    public static function isAuto(): bool
    {
        return self::current() === self::AUTO->value;
    }

    public static function isManual(): bool
    {
        return self::current() === self::MANUAL->value;
    }
}
