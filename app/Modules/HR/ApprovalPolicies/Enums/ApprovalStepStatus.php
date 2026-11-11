<?php

namespace App\Modules\HR\ApprovalPolicies\Enums;

final class ApprovalStepStatus
{
    public const PENDING = 'pending';
    public const APPROVED = 'approved';
    public const REJECTED = 'rejected';
    public const SKIPPED = 'skipped';

    public static function terminalStatuses(): array
    {
        return [
            self::APPROVED,
            self::REJECTED,
            self::SKIPPED,
        ];
    }

    public static function badgeColor(?string $status): string
    {
        return match ($status) {
            self::PENDING => 'warning',
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
            default => 'gray',
        };
    }
}
