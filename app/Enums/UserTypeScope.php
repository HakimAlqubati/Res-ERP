<?php

namespace App\Enums;

enum UserTypeScope: string

{
    case BRANCH = 'branch';
    case STORE = 'store';
    case ALL = 'all';

    public function label(): string
    {
        return match ($this) {
            self::BRANCH => 'Branch',
            self::STORE => 'Store',
            self::ALL => 'All',
        };
    }
}
