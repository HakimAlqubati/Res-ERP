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
}
