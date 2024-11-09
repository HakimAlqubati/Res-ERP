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
        $tasks = $this->getScheduleTasks($date);

        $handledSchedules = $this->handleScheuleTasks($tasks, $date);
        // return $handledSchedules;

        // Prepare data for the store function
        $storeData = [
            'current_date' => $currentDate,
            'handled_schedules' => $handledSchedules, // Use the handled schedules directly
        ];

        // Create a new Request instance with the prepared data
        $request = new Request($storeData);
        $dailyEveryDay = $request->all()['handled_schedules']['daily']['every_day'] ??  [];
        $dailySpesificDays = $request->all()['handled_schedules']['daily']['specific_days'] ??  [];
        // dd($dailyEveryDay);
        // dd($dailySpesificDays[0]['recurrence_dates'],$this->currentDate,in_array($this->currentDate,$dailySpesificDays[0]['recurrence_dates']));
        // Call the store method
        if(count($dailyEveryDay)> 0){
             $this->storeDailyEveryDay($dailyEveryDay);
        }
        if(count($dailySpesificDays)> 0){
             $this->storeDailySpecificDays($dailySpesificDays);
        }
        // return $tasks;
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
                                'steps' => $task->steps->select('title','order')->toArray(),
                            ];
                        } else if ($setDays == 'specific_days') {
                            $result[$scheduleType]['specific_days'][] = [
                                'schedule_task_id' => $task->id,
                                'schedule_task_title' => $task->title,
                                'current_date' => $date,
                                'assigned_to' => $task->assigned_to,
                                'assigned_by' => $task->assigned_by,
                                'steps' => $task->steps,
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
                            'steps' => $task->steps->pluck('title','order'),
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

    /**
     * create tasks
     */
    public function storeDailyEveryDay( $data)
    {
        // dd($request->all());
        // Validate incoming request data
        // $validatedData = $request->validate([
        //     'current_date' => 'required|date',
        //     'handled_schedules' => 'required|array',
        //     'handled_schedules.daily.every_day' => 'nullable|array',
        //     'handled_schedules.daily.every_day.*.schedule_task_id' => 'required_with:handled_schedules.daily.every_day|integer|',
        //     'handled_schedules.daily.every_day.*.schedule_task_title' => 'required_with:handled_schedules.daily.every_day|string|max:255',
        //     'handled_schedules.daily.every_day.*.assigned_to' => 'required_with:handled_schedules.daily.every_day|integer|exists:users,id',
        //     'handled_schedules.daily.every_day.*.assigned_by' => 'required_with:handled_schedules.daily.every_day|integer|exists:users,id',
        //     'handled_schedules.daily.every_day.*.steps' => 'required|array',
        //     'handled_schedules.daily.every_day.*.steps.*.title' => 'required|string|max:255', // Change here            
        //     'handled_schedules.daily.every_day.*.steps.*.order' => 'required|integer|min:1', // Change here            
        // ]);
// dd($validatedData['handled_schedules']);
        // $steps = $validatedData['handled_schedules']['daily']['every_day'][0]['steps'] ?? [];
        // $validatedData = $request->all();
        // dd($validatedData);
        // Start a database transaction
        // DB::beginTransaction();
// dd($validatedData['handled_schedules']);
        try {
            // Handle daily tasks if they exist
            // if (isset($validatedData['handled_schedules']['daily']['every_day'])) {
            if (1==1) {
                // foreach ($validatedData['handled_schedules']['daily']['every_day'] as $schedule) {
                foreach ($data as $schedule) {
                    
                    // Prepare data for task creation
                    $taskData = [
                        'title' => $schedule['schedule_task_title'] .'.'. $this->currentDate,
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
                        
                    if (is_array($schedule['steps']) && count($schedule['steps'])) {
                            foreach ($schedule['steps'] as $step) {
                                $task->steps()->create(['title' => $step['title'],'order'=> $step['order']]);
                            }
                        }
                }
            }

            // Handle specific days tasks if they exist
            // if (isset($validatedData['handled_schedules']['daily']['specific_days'])) {
            //     // dd('dd');
            //     foreach ($validatedData['handled_schedules']['daily']['specific_days'] as $schedule) {
            //         // Prepare data for task creation
            //         $taskData = [
            //             'title' => $schedule['schedule_task_title'] .'.'. now()->toDateString(),
            //             'assigned_to' => $schedule['assigned_to'],
            //             'assigned_by' => $schedule['assigned_by'],
            //             'task_status' => Task::STATUS_NEW, // Set to a default status
            //             'due_date' => now()->addDays(7), // Example: setting due date 7 days from now
            //             'is_daily' => false, // Set to false as these are not daily
            //             'start_date' => $schedule['start_date'],
            //             'end_date' => $schedule['end_date'],
            //             // 'schedule_type' => 'specific', // This can be changed as needed
            //             'branch_id' => 1, // Use appropriate branch ID if necessary
            //             'created_by' => 1,
            //             'updated_by' => 1,
            //         ];

            //         // Check if the current date matches any of the recurrence dates
            //         if (in_array($validatedData['current_date'], $schedule['recurrence_dates'])) {
            //             // Create the task
            //             $task = Task::create($taskData);

            //               // Optionally handle recurrence dates if needed
            //             Log::info("Task '{$task->title}' will recur on: {$validatedData['current_date']}");
            //         }
            //     }
            // }

            // Commit the transaction
            // DB::commit();

            return response()->json(['message' => 'Tasks created successfully.'], 201);
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
        // dd($request->all());
        // Validate incoming request data
        // $validatedData = $request->validate([
            
            
        //     'handled_schedules.daily.specific_days' => 'nullable|array',
        //     'handled_schedules.daily.specific_days.*.schedule_task_id' => 'required_with:handled_schedules.daily.specific_days|integer|exists:hr_tasks,id',
        //     'handled_schedules.daily.specific_days.*.schedule_task_title' => 'required_with:handled_schedules.daily.specific_days|string|max:255',
        //     'handled_schedules.daily.specific_days.*.assigned_to' => 'required_with:handled_schedules.daily.specific_days|integer|exists:users,id',
        //     'handled_schedules.daily.specific_days.*.assigned_by' => 'required_with:handled_schedules.daily.specific_days|integer|exists:users,id',
        //     'handled_schedules.daily.specific_days.*.steps' => 'nullable|array',
        //     'handled_schedules.daily.specific_days.*.steps.*' => 'required_with:handled_schedules.daily.specific_days|string', // Assuming steps are strings
        //     'handled_schedules.daily.specific_days.*.start_date' => 'required_with:handled_schedules.daily.specific_days|date',
        //     'handled_schedules.daily.specific_days.*.end_date' => 'required_with:handled_schedules.daily.specific_days|date',
        //     'handled_schedules.daily.specific_days.*.day_recurrence_each' => 'required_with:handled_schedules.daily.specific_days|integer',
        //     'handled_schedules.daily.specific_days.*.recurrence_dates' => 'required_with:handled_schedules.daily.specific_days|array',
        // ]);
// dd($validatedData['handled_schedules']);
        // $steps = $validatedData['handled_schedules']['daily']['every_day'][0]['steps'] ?? [];
        // $validatedData = $request->all();
        // dd($validatedData);
        // Start a database transaction
        // DB::beginTransaction();
// dd($validatedData['handled_schedules']);
        try {
            // Handle daily tasks if they exist
            // if (isset($validatedData['handled_schedules']['daily']['every_day'])) {
                foreach ($data as $schedule) {
                    if (in_array($this->currentDate,$schedule['recurrence_dates'])) {
                    
                    // Prepare data for task creation
                    $taskData = [
                        'title' => $schedule['schedule_task_title'] .'.'. $this->currentDate,
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
                        
                    if (is_array($schedule['steps']) && count($schedule['steps'])) {
                            foreach ($schedule['steps'] as $step) {
                                $task->steps()->create(['title' => $step['title'],'order'=> $step['order']]);
                            }
                        }
                }
            }

            // Handle specific days tasks if they exist
            // if (isset($validatedData['handled_schedules']['daily']['specific_days'])) {
            //     // dd('dd');
            //     foreach ($validatedData['handled_schedules']['daily']['specific_days'] as $schedule) {
            //         // Prepare data for task creation
            //         $taskData = [
            //             'title' => $schedule['schedule_task_title'] .'.'. now()->toDateString(),
            //             'assigned_to' => $schedule['assigned_to'],
            //             'assigned_by' => $schedule['assigned_by'],
            //             'task_status' => Task::STATUS_NEW, // Set to a default status
            //             'due_date' => now()->addDays(7), // Example: setting due date 7 days from now
            //             'is_daily' => false, // Set to false as these are not daily
            //             'start_date' => $schedule['start_date'],
            //             'end_date' => $schedule['end_date'],
            //             // 'schedule_type' => 'specific', // This can be changed as needed
            //             'branch_id' => 1, // Use appropriate branch ID if necessary
            //             'created_by' => 1,
            //             'updated_by' => 1,
            //         ];

            //         // Check if the current date matches any of the recurrence dates
            //         if (in_array($validatedData['current_date'], $schedule['recurrence_dates'])) {
            //             // Create the task
            //             $task = Task::create($taskData);

            //               // Optionally handle recurrence dates if needed
            //             Log::info("Task '{$task->title}' will recur on: {$validatedData['current_date']}");
            //         }
            //     }
            // }

            // Commit the transaction
            // DB::commit();

            return response()->json(['message' => 'Tasks created successfully.'], 201);
        } catch (\Exception $e) {
            // Rollback the transaction if something goes wrong
            // DB::rollBack();

            // Log the error or handle it as needed
            Log::error('Task creation failed: ' . $e->getMessage());

            return response()->json(['error' => 'Task creation failed.'], 500);
        }

    }
}
          