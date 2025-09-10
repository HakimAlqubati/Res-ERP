<?php

namespace App\Enums;

enum ProductType: string
{
    case Manufactured = 'only_mana';
    case Unmanufactured = 'only_unmana';
    case All = 'all';

    public function label(): string
    {
        return match($this) {
            self::Manufactured => 'Manufactured',
            self::Unmanufactured => 'Unmanufactured',
            self::All => 'All',
        };
    }
}
