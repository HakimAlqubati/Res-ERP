<?php

namespace App\Http\Controllers;

use App\Models\DailyTasksSettingUp;

class TestController extends Controller
{
    public function to_test_schedule_task($date)
    {
        $currentDate = $date;
        $dayName = date('l', strtotime($currentDate));
        $tasks = $this->getScheduleTasks($date);
        $handledSchedules = $this->handleScheuleTasks($tasks, $date);
        return $handledSchedules;
        return $tasks;
    }

    public function getScheduleTasks($date)
    {
        $currentDate = $date;
        $dayName = date('l', strtotime($currentDate));
        $tasks = DailyTasksSettingUp::where('active', 1)
            ->select(['id', 'title', 'assigned_by',
                'assigned_to', 'start_date', 'end_date', 'schedule_type'])
            ->where('start_date', '<=', $currentDate)
            ->where('end_date', '>=', $currentDate)
            ->whereHas('taskScheduleRequrrencePattern')
            ->whereHas('steps')
            ->with([
                'taskScheduleRequrrencePattern:task_id,recurrence_pattern',
                'steps:title,morphable_id',
            ])
            ->orderBy('id', 'desc')
            ->get()
            ->groupBy(function ($task) {
                return $task->schedule_type; // Group by recurrence_type
            })
        ;

        // Loop through the tasks and decode the recurrence pattern
        $tasks->each(function ($groupedTasks) {
            $groupedTasks->each(function ($task) {
                if (isset($task->taskScheduleRequrrencePattern->recurrence_pattern)) {
                    // Decode the JSON string to an array
                    $task->taskScheduleRequrrencePattern->recurrence_pattern = json_decode($task->taskScheduleRequrrencePattern->recurrence_pattern, true);
                }
            });
        });

        return $tasks;
    }

    public function handleScheuleTasks($tasks, $date)
    {

        $scheduleTypes = DailyTasksSettingUp::getScheduleTypesKeys();

        $result = [];

        foreach ($scheduleTypes as $scheduleType) {

            foreach ($tasks[$scheduleType] as $task) {

                if (isset($tasks[$scheduleType])) {
                    if ($scheduleType == DailyTasksSettingUp::TYPE_SCHEDULE_DAILY) {
                        $recurrencePatern = $task->taskScheduleRequrrencePattern->recurrence_pattern;
                        $setDays = $recurrencePatern['requr_pattern_set_days'];

                        if ($setDays == 'every_day') {
                            $result[$scheduleType]['every_day'][] = [
                                'schedule_task_id' => $task->id,
                                'schedule_task_title' => $task->title,
                                'current_date' => $date,
                                'assigned_to' => $task->assigned_to,
                                'assigned_by' => $task->assigned_by,
                                'steps' => $task->steps->pluck('title'),
                            ];
                        } else if ($setDays == 'specific_days') {
                            $result[$scheduleType]['specific_days'][] = [
                                'schedule_task_id' => $task->id,
                                'schedule_task_title' => $task->title,
                                'current_date' => $date,
                                'assigned_to' => $task->assigned_to,
                                'assigned_by' => $task->assigned_by,
                                'steps' => $task->steps->pluck('title'),
                                'start_date' => $task->start_date,
                                'end_date' => $task->end_date,
                                'day_recurrence_each' => $recurrencePatern['requr_pattern_day_recurrence_each'],
                                'recurrence_dates' => $this->generateDailyRecurrenceDates($task->start_date, $task->end_date, $recurrencePatern['requr_pattern_day_recurrence_each']),
                            ];
                        }
                    } else if ($scheduleType == DailyTasksSettingUp::TYPE_SCHEDULE_MONTHLY) {
                        $recurrencePatern = $task->taskScheduleRequrrencePattern->recurrence_pattern;
                        $monthlyStatus = $recurrencePatern['requr_pattern_monthly_status'];
                        if ($monthlyStatus == 'day') {
                            $result[$scheduleType]['day'][] = [
                                'schedule_task_id' => $task->id,
                                'schedule_task_title' => $task->title,
                                'current_date' => $date,
                                'assigned_to' => $task->assigned_to,
                                'assigned_by' => $task->assigned_by,
                                'steps' => $task->steps->pluck('title'),
                                'start_date' => $task->start_date,
                                'end_date' => $task->end_date,
                                'the_day_of_every' => $recurrencePatern['requr_pattern_the_day_of_every'],
                                'every_months' => $recurrencePatern['requr_pattern_months'],
                                'recurrence_dates' => $this->generateMonthlyRecurrenceDates($task->start_date, $task->end_date, $recurrencePatern['requr_pattern_the_day_of_every'], $recurrencePatern['requr_pattern_months']),
                            ];
                        } else if ($monthlyStatus == 'the') {
                            $result[$scheduleType]['the'][] = [
                                'schedule_task_id' => $task->id,
                                'schedule_task_title' => $task->title,
                                'current_date' => $date,
                                'assigned_to' => $task->assigned_to,
                                'assigned_by' => $task->assigned_by,
                                'steps' => $task->steps->pluck('title'),
                                'start_date' => $task->start_date,
                                'end_date' => $task->end_date,
                                'order' => $recurrencePatern['requr_pattern_order_name'],
                                'day' => $recurrencePatern['requr_pattern_order_day'],
                                'recurrence_dates' => $this->generateDatesByDayAndOrderBasedOnMonth($task->start_date, $task->end_date, $recurrencePatern['requr_pattern_order_name'], $recurrencePatern['requr_pattern_order_day']),
                            ];
                        }
                    } else if ($scheduleType == DailyTasksSettingUp::TYPE_SCHEDULE_WEEKLY) {

                        $recurrencePatern = $task->taskScheduleRequrrencePattern->recurrence_pattern;

                        $result[$scheduleType][] = [
                            'schedule_task_id' => $task->id,
                            'schedule_task_title' => $task->title,
                            'current_date' => $date,
                            'assigned_to' => $task->assigned_to,
                            'assigned_by' => $task->assigned_by,
                            'steps' => $task->steps->pluck('title'),
                            'start_date' => $task->start_date,
                            'end_date' => $task->end_date,
                            'week_recur_every' => $recurrencePatern['requr_pattern_week_recur_every'],
                            'weekly_days' => $recurrencePatern['requr_pattern_weekly_days'],
                            'recurrence_dates' => $this->generateWeeklyDatesBasedOnSpecificDays($task->start_date, $task->end_date, $recurrencePatern['requr_pattern_week_recur_every'], $recurrencePatern['requr_pattern_weekly_days']),
                        ];
                    }
                }

            }

        }

        return $result;
    }

    /**
     * to return list of dates each how many day between two dates [start,end]
     */
    public function generateDailyRecurrenceDates($startDate, $endDate, $dayRecurrenceEach)
    {
        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);

        // Add one day to the end date to ensure it includes the end date in the period
        $end->modify('+1 day');

        $interval = new \DateInterval("P{$dayRecurrenceEach}D"); // "P3D" means 3 days
        $datePeriod = new \DatePeriod($start, $interval, $end);

        $dates = [];
        foreach ($datePeriod as $date) {
            $dates[] = $date->format('Y-m-d');
        }

        return $dates;
    }

    /**
     * to return list of dates each how many day between two dates [start,end]
     */
    public function generateMonthlyRecurrenceDates($startDate, $endDate, $dayOfEvery, $everyMonths)
    {
        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);

        $dates = [];

        // Adjust start date to the specified day of the month
        if ((int) $start->format('d') != $dayOfEvery) {
            $start->setDate((int) $start->format('Y'), (int) $start->format('m'), $dayOfEvery);
        }

        while ($start <= $end) {
            $dates[] = $start->format('Y-m-d');
            // Move to the next recurrence (every N months)
            $start->modify("+{$everyMonths} months");
            $start->setDate((int) $start->format('Y'), (int) $start->format('m'), $dayOfEvery);
        }

        return $dates;
    }

    /**
     * to return list of dates by day and order based on months between two dates [start,end]
     */
    public function generateDatesByDayAndOrderBasedOnMonth($startDate, $endDate, $order, $day)
    {
        // Validate order and day inputs
        $orders = [
            'first' => 0,
            'second' => 1,
            'third' => 2,
            'fourth' => 3,
            'fifth' => 4,
        ];

        $daysOfWeek = [
            'Monday' => 'Monday',
            'Tuesday' => 'Tuesday',
            'Wednesday' => 'Wednesday',
            'Thursday' => 'Thursday',
            'Friday' => 'Friday',
            'Saturday' => 'Saturday',
            'Sunday' => 'Sunday',
        ];

        // Check if the order and day are valid
        if (!array_key_exists($order, $orders) || !array_key_exists($day, $daysOfWeek)) {
            throw new \InvalidArgumentException("Invalid order or day.");
        }

        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);
        $end->modify('last day of this month'); // Ensure we consider the last month

        $dates = [];
        $dayOfWeek = strtolower($day); // Convert to lowercase for comparison

        // Loop through each month between the start and end date
        while ($start <= $end) {
            // Set to the first day of the month
            $firstDayOfMonth = new \DateTime($start->format('Y-m-01'));

            // Find the first occurrence of the specified day
            $firstOccurrence = $firstDayOfMonth->modify("first $dayOfWeek of this month");

            // Move to the desired order (e.g., first, second, etc.)
            $desiredDate = clone $firstOccurrence;
            $desiredDate->modify("+{$orders[$order]} week");

            // Check if the desired date is within the range
            if ($desiredDate >= new \DateTime($startDate) && $desiredDate <= new \DateTime($endDate)) {
                $dates[] = $desiredDate->format('Y-m-d');
            }

            // Move to the next month
            $start->modify('+1 month');
        }

        return $dates;
    }

    /**
     * to get list of dates on specific days in every how many week based on months between two dates [start,end]
     */
    public function generateWeeklyDatesBasedOnSpecificDays($startDate, $endDate, $weekRecurEvery, $weeklyDays)
    {
        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);
        $end = $end->modify('+1 day'); // Include end date

        $dates = [];
        $interval = new \DateInterval("P{$weekRecurEvery}W"); // Weekly interval

        // Loop through each week
        for ($current = $start; $current < $end; $current->add($interval)) {
            // Loop through each day
            foreach ($weeklyDays as $day) {
                // Get the first occurrence of the specified day in the week
                $currentDay = clone $current;
                $currentDay->modify("next $day");
                // Check if this occurrence is within the date range
                if ($currentDay >= $start && $currentDay < $end) {
                    $dates[] = [
                        'date' => $currentDay->format('Y-m-d'),
                        'day_name' => $currentDay->format('l'), // Get the day name
                    ];
                }
            }
        }

        // Ensure unique dates based on the 'date' key
        $uniqueDates = [];
        foreach ($dates as $date) {
            $uniqueDates[$date['date']] = $date; // Use the date as the key to maintain uniqueness
        }

        return array_values($uniqueDates); // Return values as an indexed array
    }

}
