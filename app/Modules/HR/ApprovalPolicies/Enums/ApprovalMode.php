<?php

namespace App\Modules\HR\ApprovalPolicies\Enums;

final class ApprovalMode
{
    public const DIRECT_MANAGER = 'direct_manager';
    public const BRANCH_MANAGER = 'branch_manager';
    public const MANAGER_CHAIN = 'manager_chain';
    public const CUSTOM_USERS = 'custom_users';

    public static function values(): array
    {
        return [
            self::DIRECT_MANAGER,
            self::BRANCH_MANAGER,
            self::MANAGER_CHAIN,
            self::CUSTOM_USERS,
        ];
    }
}
