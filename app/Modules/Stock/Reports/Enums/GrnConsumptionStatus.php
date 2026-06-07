<?php

namespace App\Modules\Stock\Reports\Enums;

/**
 * Defines the consumption states for an entire Goods Received Note (GRN).
 */
enum GrnConsumptionStatus: string
{
    /**
     * The GRN has at least one item that is not fully consumed.
     */
    case IN_PROGRESS = 'in_progress';

    /**
     * All items within the GRN have been 100% consumed.
     */
    case FULLY_COMPLETED = 'fully_completed';

    /**
     * Get the human-readable label for the status.
     */
    public function label(): string
    {
        return match($this) {
            self::IN_PROGRESS => 'In Progress',
            self::FULLY_COMPLETED => 'Fully Completed',
        };
    }

    /**
     * Get the CSS class for the status badge.
     */
    public function badgeClass(): string
    {
        return match($this) {
            self::IN_PROGRESS => 'badge yellow',
            self::FULLY_COMPLETED => 'badge green',
        };
    }
}
