<?php

namespace App\Modules\HR\ApprovalPolicies\Enums;

final class ApprovalPolicyStepType
{
    public const DIRECT_MANAGER = 'direct_manager';
    public const BRANCH_MANAGER = 'branch_manager';
    public const MANAGER_LEVEL = 'manager_level';
    public const CUSTOM_USER = 'custom_user';
    public const CUSTOM_ROLE = 'custom_role';

    public static function values(): array
    {
        return [
            self::DIRECT_MANAGER,
            self::BRANCH_MANAGER,
            self::MANAGER_LEVEL,
            self::CUSTOM_USER,
            self::CUSTOM_ROLE,
        ];
    }
}
