<?php

namespace App\Enums;

enum Role: int
{
    case SUPER_ADMIN = 1;
    case OPS_MANAGER = 3;
    case CUSTOMER = 4;
    case STORE = 5;
    case DRIVER = 6;
    case BRANCH_MANAGER = 7;
    case BRANCH_STAFF = 8;
    case ACCOUNTANT = 9;
    case SUPPLIER = 10;
    case PANEL_USER = 11;
    case MAINTENANCE_MANAGER = 14;
    case SUPERVISOR = 15;
    case FINANCE_MANAGER = 16;
    case ATTENDANCE = 17;
    case OWNER = 18;
    case HR = 19;

    public function label(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'super_admin',
            self::OPS_MANAGER => 'Ops Manager',
            self::CUSTOMER => 'Customer',
            self::STORE => 'Store',
            self::DRIVER => 'Driver',
            self::BRANCH_MANAGER => 'Branch Manager',
            self::BRANCH_STAFF => 'Branch Staff',
            self::ACCOUNTANT => 'Accountant',
            self::SUPPLIER => 'Supplier',
            self::PANEL_USER => 'panel_user',
            self::MAINTENANCE_MANAGER => 'Maintenance Manager',
            self::SUPERVISOR => 'Supervisor',
            self::FINANCE_MANAGER => 'Finance Manager',
            self::ATTENDANCE => 'Attendance',
            self::OWNER => 'Owner',
            self::HR => 'HR',
        };
    }

    /**
     * Get all enum values (IDs) as an array.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get all enum labels (names) as an array.
     */
    public static function labels(): array
    {
        return array_map(fn($role) => $role->label(), self::cases());
    }

    /**
     * Get an associative array of [value => label].
     * Extremely useful for dropdowns and options lists.
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }
        return $options;
    }
}
