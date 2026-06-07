<?php

namespace App\Modules\Stock\Reports\Enums;

/**
 * Defines the consumption states for individual inventory items or products.
 */
enum ItemConsumptionStatus: string
{
    /**
     * Stock has been received but not a single unit has been consumed yet.
     */
    case UNTOUCHED = 'untouched';

    /**
     * Stock consumption has started, meaning some units have been dispatched 
     * but there is still remaining balance > 0.
     */
    case CONSUMING = 'consuming';

    /**
     * The entire stock entry or product quantity has been 100% consumed.
     * Remaining balance is exactly 0.
     */
    case COMPLETED = 'completed';

    /**
     * Get the human-readable label for the status.
     */
    public function label(): string
    {
        return match($this) {
            self::UNTOUCHED => 'Untouched',
            self::CONSUMING => 'Consuming',
            self::COMPLETED => 'Completed',
        };
    }

    /**
     * Get the CSS class for the status badge.
     */
    public function badgeClass(): string
    {
        return match($this) {
            self::UNTOUCHED => 'badge gray',
            self::CONSUMING => 'badge blue',
            self::COMPLETED => 'badge green',
        };
    }
}
