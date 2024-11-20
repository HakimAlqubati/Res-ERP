<?php

namespace App\Http\Controllers;

use App\Models\DailyTasksSettingUp;
use App\Models\Employee;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TestController extends Controller
{
    public $currentDate = '';
    public function to_test_schedule_task($date)
    {
        $currentDate = $date;
        $this->currentDate = $date;

        $dayName = date('l', strtotime($currentDate));
        $weeklyTasks = $this->getScheduleTasksWeekly($date);

        $dailyTasks = $this->getScheduleTasksDaily($date);
        $monthlyTasks = $this->getScheduleTasksMonthly($date);
        // return $monthlyTasks;
        $handledSchedulesWeekly = $this->handleScheuleTasks($weeklyTasks, $date);
        // return $handledSchedulesWeekly;
        $handledSchedulesMonthly = $this->handleScheuleTasks($monthlyTasks, $date);
        $handledSchedulesDaily = $this->handleScheuleTasks($dailyTasks, $date);
        //  return $monthlyTasks;
        // return[count($handledSchedulesMonthly['monthly']),$handledSchedulesMonthly['monthly']];
        $storeDataResponse = [];
        if (isset($handledSchedulesWeekly['weekly']) && is_array($handledSchedulesWeekly['weekly']) && count($handledSchedulesWeekly['weekly']) > 0) {

            $storeDataResponse[] = $this->storeWeeklyTasks($handledSchedulesWeekly['weekly']);
            // return $handledSchedulesWeekly['weekly'];
        }

        if (isset($handledSchedulesMonthly['monthly']) && is_array(value: $handledSchedulesMonthly['monthly']) && count($handledSchedulesMonthly['monthly']) > 0) {
            $storeDataResponse[] = $this->storeMonthlyTasks($handledSchedulesMonthly['monthly']);
            // return $storeDataResponse[];
        }
        
        if (count($handledSchedulesDaily) > 0) {

            // Prepare data for the store function
            $storeData = [
                'current_date' => $currentDate,
                'handled_schedules' => $handledSchedulesDaily, // Use the handled schedules directly
            ];

            // Create a new Request instance with the prepared data
            $request = new Request($storeData);
            $dailyEveryDay = $request->all()['handled_schedules']['daily']['every_day'] ?? [];
            $dailySpesificDays = $request->all()['handled_schedules']['daily']['specific_days'] ?? [];
            // dd($dailyEveryDay,$dailySpesificDays);
            // Call the store method
            if (count($dailyEveryDay) > 0) {
                $storeDataResponse[] = $dailyEveryDay = $this->storeDailyEveryDay($dailyEveryDay);
            }
            if (count($dailySpesificDays) > 0) {
                $storeDataResponse[] = $this->storeDailySpecificDays($dailySpesificDays);
            }

        }
        return $storeDataResponse;

    }

    public function getScheduleTasksDaily($date)
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
            ->where('schedule_type', DailyTasksSettingUp::TYPE_SCHEDULE_DAILY)
            ->with([
                'taskScheduleRequrrencePattern:task_id,recurrence_pattern',
                'steps:title,morphable_id,order',
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
    public function getScheduleTasksWeekly($date)
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
            ->where('schedule_type', DailyTasksSettingUp::TYPE_SCHEDULE_WEEKLY)
            ->with([
                'taskScheduleRequrrencePattern:task_id,recurrence_pattern',
                'steps:title,morphable_id,order',
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

    public function getScheduleTasksMonthly($date)
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
            ->where('schedule_type', DailyTasksSettingUp::TYPE_SCHEDULE_MONTHLY)
            ->with([
                'taskScheduleRequrrencePattern:task_id,recurrence_pattern',
                'steps:title,morphable_id,order',
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

            if (isset($tasks[$scheduleType])) {
                foreach ($tasks[$scheduleType] as $task) {
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
                                'steps' => $task->steps->select('title', 'order')->toArray(),
                            ];
                        } else if ($setDays == 'specific_days') {
                            $result[$scheduleType]['specific_days'][] = [
                                'schedule_task_id' => $task->id,
                                'schedule_task_title' => $task->title,
                                'current_date' => $date,
                                'assigned_to' => $task->assigned_to,
                                'assigned_by' => $task->assigned_by,
                                'steps' => $task->steps->select('title', 'order')->toArray(),
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
                            // dd('dd',$task,$recurrencePatern);
                            $result[$scheduleType]['day'][] = [
                                'schedule_task_id' => $task->id,
                                'schedule_task_title' => $task->title,
                                'current_date' => $date,
                                'assigned_to' => $task->assigned_to,
                                'assigned_by' => $task->assigned_by,
                                'steps' => $task->steps->select('title', 'order')->toArray(),
                                'start_date' => $task->start_date,
                                'end_date' => $task->end_date,
                                'the_day_of_every' => $recurrencePatern['requr_pattern_the_day_of_every'],
                                'every_months' => $recurrencePatern['requr_pattern_months'],
                                'recurrence_dates' => $this->generateMonthlyRecurrenceDates($task->start_date, $task->end_date, $recurrencePatern['requr_pattern_the_day_of_every'], $recurrencePatern['requr_pattern_months'], $task),
                            ];
                        } else if ($monthlyStatus == 'the') {
                            $result[$scheduleType]['the'][] = [
                                'schedule_task_id' => $task->id,
                                'schedule_task_title' => $task->title,
                                'current_date' => $date,
                                'assigned_to' => $task->assigned_to,
                                'assigned_by' => $task->assigned_by,
                                'steps' => $task->steps->select('title', 'order')->toArray(),
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
                            'steps' => $task->steps->select('title', 'order')->toArray(),
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
        try {
            // Ensure $dayRecurrenceEach is a positive integer
            $dayRecurrenceEach = (int) $dayRecurrenceEach;
            if ($dayRecurrenceEach <= 0) {
                throw new \InvalidArgumentException("Invalid day recurrence interval.");
            }

            // Validate date formats
            if (!strtotime($startDate) || !strtotime($endDate)) {
                throw new \InvalidArgumentException("Invalid start or end date format.");
            }

            $start = new \DateTime($startDate);
            $end = new \DateTime($endDate);

            // Add one day to the end date to ensure it includes the end date in the period
            $end->modify('+1 day');

            // Instantiate DateInterval and DatePeriod
            $interval = new \DateInterval("P{$dayRecurrenceEach}D");
            $datePeriod = new \DatePeriod($start, $interval, $end);

            $dates = [];
            foreach ($datePeriod as $date) {
                $dates[] = $date->format('Y-m-d');
            }

            return $dates;
        } catch (\Exception $e) {
            Log::error('generateDailyRecurrenceDates error: ' . $e->getMessage());
            return ['error' => 'Date generation failed'];
        }
    }

    /**
     * to return list of dates each how many day between two dates [start,end]
     */
    public function generateMonthlyRecurrenceDates($startDate, $endDate, $dayOfEvery, $everyMonths, $task)
    {
        // dd($startDate,$endDate,$dayOfEvery,$everyMonths,$task);
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

    /**
     * create tasks
     */
    public function storeDailyEveryDay($data)
    {
        // dd($data);
        // DB::beginTransaction();
        try {
            // Handle daily tasks if they exist

            // foreach ($validatedData['handled_schedules']['daily']['every_day'] as $schedule) {
            foreach ($data as $schedule) {

                // Prepare data for task creation
                $taskData = [
                    'title' => $schedule['schedule_task_title'] . '.' . $this->currentDate,
                    'assigned_to' => $schedule['assigned_to'],
                    'assigned_by' => $schedule['assigned_by'],
                    'task_status' => Task::STATUS_NEW, // Set to a default status
                    'due_date' => $this->currentDate, // Example: setting due date 7 days from now
                    'is_daily' => false,

                    'branch_id' => Employee::find($schedule['assigned_to'])?->branch_id, // Use appropriate branch ID if necessary
                    'created_by' => 1,
                    'updated_by' => 1,
                ];

                //         // Create the task
                $task = Task::create($taskData);
                
                if (!empty($schedule['steps']) && is_array($schedule['steps'])) {
                    $task->steps()->createMany($schedule['steps']);
                }
                // $steps = $schedule['steps']->toArray();
                // if (is_array($steps) && count($steps)) {
                //     foreach ($steps as $step) {
                //         $task->steps()->create(['title' => $step['title'], 'order' => $step['order']]);
                //     }
                // }
            }

            // Commit the transaction
            // DB::commit();

            return response()->json(['message' => 'Daily tasks created successfully.'], 201);
        } catch (\Exception $e) {
            // Rollback the transaction if something goes wrong
            // DB::rollBack();

            // Log the error or handle it as needed
            Log::error('Task creation failed: ' . $e->getMessage());

            return response()->json(['error' => 'Task creation failed.'], 500);
        }

    }
    public function storeDailySpecificDays($data)
    {

        // Start a database transaction
        DB::beginTransaction();

        try {

            foreach ($data as $schedule) {
                if (in_array($this->currentDate, $schedule['recurrence_dates'])) {

                    // Prepare data for task creation
                    $taskData = [
                        'title' => $schedule['schedule_task_title'] . '.' . $this->currentDate,
                        'assigned_to' => $schedule['assigned_to'],
                        'assigned_by' => $schedule['assigned_by'],
                        'task_status' => Task::STATUS_NEW, // Set to a default status
                        'due_date' => $this->currentDate, // Example: setting due date 7 days from now
                        'is_daily' => false,

                        'branch_id' => Employee::find($schedule['assigned_to'])?->branch_id, // Use appropriate branch ID if necessary
                        'created_by' => 1,
                        'updated_by' => 1,
                    ];
// dd(is_array($schedule['steps']->toArray()));
                    //         // Create the task
                    $task = Task::create($taskData);
                    // $steps = $schedule['steps']->toArray();
                    if (!empty($schedule['steps']) && is_array($schedule['steps'])) {
                        $task->steps()->createMany($schedule['steps']);
                    }
                    // if (is_array($steps) && count($steps)) {
                    //     foreach ($steps as $step) {
                    //         $task->steps()->create(['title' => $step['title'], 'order' => $step['order']]);
                    //     }
                    // }
                }
            }

            // Commit the transaction
            DB::commit();

            return response()->json(['message' => 'Daily tasks created successfully.'], 201);
        } catch (\Exception $e) {
            // Rollback the transaction if something goes wrong
            DB::rollBack();

            // Log the error or handle it as needed
            Log::error('Task creation failed: ' . $e->getMessage());

            return response()->json(['error' => 'Task creation failed.'], 500);
        }

    }

    /**
     * Stores weekly tasks based on recurrence dates.
     */
    public function storeWeeklyTasks($data)
    {
        try {
            foreach ($data as $schedule) {
                // Check if the current date exists in recurrence_dates array
                $dateMatch = collect($schedule['recurrence_dates'])->contains(function ($recurrenceDate) {
                    return $recurrenceDate['date'] === $this->currentDate;
                });

                if ($dateMatch) {
                    // Prepare data for task creation
                    $taskData = [
                        'title' => "{$schedule['schedule_task_title']}.{$this->currentDate}",
                        'assigned_to' => $schedule['assigned_to'],
                        'assigned_by' => $schedule['assigned_by'],
                        'task_status' => Task::STATUS_NEW, // Default status
                        'due_date' => $this->currentDate, // Current date as due date
                        'is_daily' => false,
                        'branch_id' => Employee::find($schedule['assigned_to'])?->branch_id, // Get branch ID if necessary
                        'created_by' => 1, // Default to authenticated user
                        'updated_by' => 1,
                    ];

                    // Create the task
                    $task = Task::create($taskData);

                    // Add steps if they exist
                    if (!empty($schedule['steps']) && is_array($schedule['steps'])) {
                        $task->steps()->createMany($schedule['steps']);
                    }
                }else{

                    return response()->json(['message' => 'No weekly tasks found for the current date'], 201);
                }
            }

            // Return a success response
            return response()->json(['message' => 'Weekly tasks created successfully.'], 201);
        } catch (\Exception $e) {
            // Log the error
            Log::error('Weekly task creation failed: ' . $e->getMessage(), ['data' => $data]);

            // Return an error response
            return response()->json(['error' => 'Weekly task creation failed.'], 500);
        }
    }

    /**
     * Stores weekly tasks based on recurrence dates.
     */
    public function storeMonthlyTasks($data)
    {
        // Merge the arrays into one single array
        // $mergedData = array_merge($data['the'], $data['day']);
        $mergedData = array_merge(
            $data['the'] ?? [],
            $data['day'] ?? []
        );
        DB::beginTransaction();
        try {
            $tasksCreated = false; // Flag to track if any tasks are created
            foreach ($mergedData as $schedule) {
                // dd($schedule,$schedule['steps']);
                // Check if recurrence_dates is valid
                if (!isset($schedule['recurrence_dates']) || !is_array($schedule['recurrence_dates'])) {
                    continue; // Skip if recurrence_dates is invalid
                }
    
                // Check if the current date exists in recurrence_dates array
                if (in_array($this->currentDate, $schedule['recurrence_dates'])) {
                    // Prepare data for task creation
                    $taskData = [
                        'title' => "{$schedule['schedule_task_title']}.{$this->currentDate}",
                        'assigned_to' => $schedule['assigned_to'],
                        'assigned_by' => $schedule['assigned_by'],
                        'task_status' => Task::STATUS_NEW, // Default status
                        'due_date' => $this->currentDate, // Current date as due date
                        'is_daily' => false,
                        'branch_id' => Employee::find($schedule['assigned_to'])?->branch_id, // Get branch ID if necessary
                        'created_by' => 1, // Default to authenticated user
                        'updated_by' => 1,
                    ];
    
                    // Create the task
                    $task = Task::create($taskData);
    
                    // Add steps if they exist
                    if (!empty($schedule['steps']) && is_array($schedule['steps'])) {
                        $task->steps()->createMany($schedule['steps']);
                    }
    
                    $tasksCreated = true; // Set flag to true
                }
            }
    
            DB::commit();
    
            // Return appropriate response based on tasks created
            if ($tasksCreated) {
                return response()->json(['message' => 'Monthly tasks created successfully.'], 201);
            } else {
                return response()->json(['message' => 'No monthly tasks found for the current date.'], 200);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            // Log the error with detailed information
            Log::error('Monthly task creation failed', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'data' => $data,
                'merged_data' => $mergedData,
            ]);
    
            // Return an error response
            return response()->json(['error' => 'Monthly task creation failed. Please check the logs for more details.'], 500);
        }
    }
    
}
