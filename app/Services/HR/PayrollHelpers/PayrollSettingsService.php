<?php

namespace App\Services\HR\PayrollHelpers;

use Carbon\Carbon;

class PayrollSettingsService
{
    public function getDaysInMonth($date)
    {
        // Get days in month (from settings)
        return 30;
    }

    public function getDaysMonthReal($date)
    {
        // If date is not provided, use the current date
        $date = $date ?? date('Y-m-d');

        // Extract the month and year from the provided date
        $currentMonth = date('m', strtotime($date));
        $currentYear = date('Y', strtotime($date));

        // Calculate the number of days in the given month and year
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $currentMonth, $currentYear);
        return $daysInMonth;
    }

    public function getEndOfMonthDate($year = null, $month = null)
    {
           if (!$year) {
            $year = date('Y');
        }
        if (!$month) {
            $month = date('m');
        }

        // Fetch settings
        $useStandard = setting('use_standard_end_of_month'); // Default: true (standard month end)
        $customDay = setting('end_of_month_day'); // Default custom end day: 30

        if ($useStandard) {

            // Standard mode: Use the actual end of the month (e.g., 28, 30, or 31)
            $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();
            // Calculate the start date (30 days before the end date)
            $startDate = $endDate->copy()->startOfMonth();
        } else {
            // Custom mode: Use the user-defined day, ensuring it does not exceed the real last day
            $lastDay = Carbon::createFromDate($year, $month, 1)->endOfMonth()->day;
            $endDate = Carbon::createFromDate($year, $month, min($customDay, $lastDay));
            $endTemp = $endDate->copy(); // Creates a separate instance
            $previousMonth = $endTemp->subMonth(); // This no longer affects $endDate
            $daysInMonth = $previousMonth->daysInMonth; // Get the days in the previous month
            $daysInMonth -= 1;
            $startDate = $endDate->copy()->subDays($daysInMonth);
        }


        return [
            'year' => $year,
            'start_month' => $startDate->toDateString(),
            'end_month' => $endDate->toDateString(),
        ];
    }

    public function getMonthOptionsBasedOnSettings()
    {
          $options = [];
        $currentDate = Carbon::now();
        $useStandard = setting('use_standard_end_of_month', true); // Default: true

        for ($i = 0; $i < 12; $i++) {
            $monthDate = $currentDate->copy()->subMonths($i); // Get past months

            if ($useStandard) {
                // Standard month format (Full name with year)
                $monthName = $monthDate->format('F Y');
                $monthYear = $monthDate->format('F Y');
                $monthNameOnly = $monthDate->format('F');
                $options[$monthYear] = $monthName;
            } else {
                // Custom Start and End Date Based on end_of_month_day
                $monthYear = $monthDate->format('F Y');

                $endOfMonthData = getEndOfMonthDate($monthDate->year, $monthDate->month);
                $formattedPeriod = "{$monthYear} ({$endOfMonthData['start_month']} - {$endOfMonthData['end_month']})";

                $options[$monthYear] = $formattedPeriod;
            }
        }

        return $options;
    }

    public function getMonthOptionsBasedOnWithStatis()
    {
        $options = [];
        $currentDate = new \DateTime();
        for ($i = 0; $i < 12; $i++) {
            $monthDate = (clone $currentDate)->sub(new \DateInterval("P{$i}M")); // Subtract months
            $monthName = $monthDate->format('F Y'); // Full month name with year
            $monthNameOnly = $monthDate->format('F'); // Full month name
            // $monthValue = $monthDate->format('Y-m'); // Value in Y-m format

            $options[$monthNameOnly] = $monthName;
        }

        return $options;
    }
}