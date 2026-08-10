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
}
