<?php

namespace App\Enums;

enum BranchType: string
{
    case BRANCH = 'branch';
    case CENTRAL_KITCHEN = 'central_kitchen';
    case HQ = 'hq';
    case POPUP = 'popup';
    case RESELLER = 'reseller';

    public function label(): string
    {
        return match ($this) {
            self::BRANCH => __('lang.branch'),
            self::CENTRAL_KITCHEN => __('lang.central_kitchen'),
            self::HQ => __('lang.hq'),
            self::POPUP => __('lang.popup_branch'),
            self::RESELLER => __('lang.reseller'),
        };
    }
}
