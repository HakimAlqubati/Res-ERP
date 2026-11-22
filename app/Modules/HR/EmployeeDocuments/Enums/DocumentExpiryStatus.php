<?php

declare(strict_types=1);

namespace App\Modules\HR\EmployeeDocuments\Enums;

enum DocumentExpiryStatus: string
{
    case Expired = 'expired';
    case ExpiringSoon = 'expiring_soon';
    case Valid = 'valid';
    case NoExpiry = 'no_expiry';

    public function label(): string
    {
        return match ($this) {
            self::Expired => __('lang.doc_status_expired'),
            self::ExpiringSoon => __('lang.doc_status_expiring_soon'),
            self::Valid => __('lang.doc_status_valid'),
            self::NoExpiry => __('lang.doc_status_no_expiry'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Expired => 'danger',
            self::ExpiringSoon => 'warning',
            self::Valid => 'success',
            self::NoExpiry => 'gray',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Expired => 'heroicon-o-x-circle',
            self::ExpiringSoon => 'heroicon-o-exclamation-triangle',
            self::Valid => 'heroicon-o-check-circle',
            self::NoExpiry => 'heroicon-o-minus-circle',
        };
    }

    /**
     * @return array<string, string>
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
