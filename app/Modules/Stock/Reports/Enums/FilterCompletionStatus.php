<?php

namespace App\Modules\Stock\Reports\Enums;

/**
 * Filter enum for completion status.
 */
enum FilterCompletionStatus: string
{
    case ALL = 'all';
    case COMPLETED = 'completed';
    case INCOMPLETE = 'incomplete';

    public function label(): string
    {
        return match($this) {
            self::ALL => 'All Statuses',
            self::COMPLETED => 'Completed Only',
            self::INCOMPLETE => 'Incomplete Only',
        };
    }
}
