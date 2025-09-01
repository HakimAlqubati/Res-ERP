<?php

namespace App\Models\Branch\Traits;

trait BranchConstants
{
    public const TYPE_BRANCH          = 'branch';
    public const TYPE_CENTRAL_KITCHEN = 'central_kitchen';
    public const TYPE_HQ              = 'hq';
    public const TYPE_POPUP           = 'popup';
    public const TYPE_RESELLER        = 'reseller';

    public const TYPES = [
        self::TYPE_BRANCH,
        self::TYPE_CENTRAL_KITCHEN,
        self::TYPE_HQ,
        self::TYPE_POPUP,
        self::TYPE_RESELLER,
    ];
}
