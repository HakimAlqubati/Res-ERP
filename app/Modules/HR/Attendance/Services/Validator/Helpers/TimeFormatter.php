<?php

namespace App\Modules\HR\Attendance\Services\Validator\Helpers;

/**
 * مساعد لتنسيق الوقت
 */
class TimeFormatter
{
    /**
     * تنسيق الوقت المتبقي بشكل مقروء
     */
    public static function formatRemainingTime(int $totalSeconds): string
    {
        if ($totalSeconds >= 60) {
            $minutes = floor($totalSeconds / 60);
            $seconds = $totalSeconds % 60;

            if ($seconds > 0) {
                return $minutes . ' ' . __('notifications.minutue') . ' ' .
                    $seconds . ' ' . __('notifications.second');
            }

            return $minutes . ' ' . __('notifications.minutue');
        }

        return $totalSeconds . ' ' . __('notifications.second');
    }
}
