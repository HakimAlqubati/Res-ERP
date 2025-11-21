<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\EmployeeOvertimeResource\Schema;

use App\Models\Branch;
use App\Models\Employee;
use Carbon\Carbon;
use App\Models\EmployeeOvertime;
use Filament\Schemas\Components\Grid;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Illuminate\Validation\Rules\Unique;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use DateTime;
use DateInterval;

class EmployeeOvertimeForm
{
    public static function configure($schema)
    {
        return $schema
            ->components(
                [

                    Fieldset::make()->columnSpanFull()
                        ->columns(3)
                        ->label('Set branch employees & the Date')->columnSpanFull()
                        ->schema([

                            Select::make('branch_id')->label('Branch')
                                ->helperText('Select to populate the branch employees')
                                ->required()->searchable()
                                ->live()
                                ->options(Branch::where('active', 1)
                                    ->forBranchManager('id')
                                    ->select('name', 'id')->get()->pluck('name', 'id'))
                                ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                    $employees             = Employee::where('branch_id', $state)
                                        ->forBranchManager()
                                        ->where('active', 1)->select('name', 'id as employee_id')->get()->toArray();
                                    $employeesWithOvertime = [];
                                    foreach ($employees as $employeeData) {
                                        // Fetch the employee using the employee_id from the provided data
                                        $employee = Employee::find($employeeData['employee_id']);

                                        // dd($employee);
                                        // Ensure the employee exists before calling the overtime calculation
                                        if ($employee) {
                                            // Calculate overtime for the specified date
                                            $overtimeResults = $employee->calculateEmployeeOvertime($employee, $get('date'));

                                            // Only add to the results if there are overtime records
                                            if (! empty($overtimeResults)) {
                                                $employeesWithOvertime[] = [
                                                    'employee_id'      => $employee->id,
                                                    'overtime_details' => $overtimeResults,
                                                    'overtime_hours'   => $overtimeResults[0]['overtime_hours'],
                                                    'start_time'       => $overtimeResults[0]['overtime_start_time'],
                                                    'end_time'         => $overtimeResults[0]['overtime_end_time'],
                                                ];
                                            }
                                        }
                                    }

                                    // Populate the Repeater with employees
                                    $set('employees', array_map(function ($employee) {
                                        return [
                                            'employee_id' => $employee['employee_id'],

                                            'start_time'  => $employee['start_time'],
                                            'end_time'    => $employee['end_time'],

                                            'notes'       => null,
                                            // 'hours' => 55,
                                            'hours'       => $employee['overtime_hours'],
                                        ];
                                    }, $employeesWithOvertime));
                                }),

                            Select::make('type')
                                ->label('Type')
                                ->options(EmployeeOvertime::getTypes())
                                ->live()
                                ->default(EmployeeOvertime::TYPE_BASED_ON_DAY)
                                ->required(),
                            DatePicker::make('date')
                                ->visible(fn($get) => $get('type') === EmployeeOvertime::TYPE_BASED_ON_DAY)
                                ->label('Overtime Date')
                                ->default(date('Y-m-d'))
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                    $employees             = Employee::where('branch_id', $get('branch_id'))->where('active', 1)->select('name', 'id as employee_id')->get()->toArray();
                                    $employeesWithOvertime = [];
                                    foreach ($employees as $employeeData) {
                                        // Fetch the employee using the employee_id from the provided data
                                        $employee = Employee::find($employeeData['employee_id']);

                                        // Ensure the employee exists before calling the overtime calculation
                                        if ($employee) {
                                            // Calculate overtime for the specified date
                                            $overtimeResults = $employee->calculateEmployeeOvertime($employee, $state);

                                            // Only add to the results if there are overtime records
                                            if (! empty($overtimeResults)) {
                                                $employeesWithOvertime[] = [
                                                    'employee_id'      => $employee->id,
                                                    'overtime_details' => $overtimeResults,
                                                    'overtime_hours'   => $overtimeResults[0]['overtime_hours'],
                                                    // 'start_time' => $overtimeResults[0]['overtime_start_time'],
                                                    // 'end_time' => $overtimeResults[0]['overtime_end_time'],
                                                    'start_time'       => $overtimeResults[0]['check_in_time'],
                                                    'end_time'         => $overtimeResults[0]['check_out_time'],
                                                ];
                                            }
                                        }
                                    }

                                    // Populate the Repeater with employees
                                    $set('employees', array_map(function ($employee) {
                                        return [
                                            'employee_id' => $employee['employee_id'],

                                            'start_time'  => $employee['start_time'],
                                            'end_time'    => $employee['end_time'],

                                            'notes'       => null,
                                            'hours'       => $employee['overtime_hours'],
                                        ];
                                    }, $employeesWithOvertime));
                                }),
                            Select::make('month_year')->label('Month')->hiddenOn('view')
                                ->required()
                                ->visible(fn($get) => $get('type') === EmployeeOvertime::TYPE_BASED_ON_MONTH)
                                ->options(function () {
                                    $options     = [];
                                    $currentDate = new \DateTime();
                                    for ($i = 0; $i < 12; $i++) {
                                        $monthDate              = (clone $currentDate)->sub(new \DateInterval("P{$i}M"));
                                        $monthName              = $monthDate->format('F Y');
                                        $YearAndMonth           = $monthDate->format('Y-m');
                                        $options[$YearAndMonth] = $monthName;
                                    }
                                    return $options;
                                })->live()
                                ->default(now()->format('F'))
                                ->afterStateUpdated(function (Get $get, Set $set, $state) {

                                    $branchId = $get('branch_id');

                                    $employees                    = calculateAutoWeeklyLeaveDataForBranch($state, $branchId);
                                    $employeesWithWeekEndOvertime = [];
                                    foreach ($employees as $employeeId => $employeeData) {
                                        // Fetch the employee using the employee_id from the provided data
                                        $employee = $employeeData;;

                                        // Ensure the employee exists before calling the overtime calculation
                                        if ($employee) {
                                            // Calculate overtime for the specified date
                                            // $overtimeResults = $employee->calculateEmployeeOvertime($employee, $state);

                                            // Only add to the results if there are overtime records
                                            // if (!empty($overtimeResults)) {
                                            $employeesWithWeekEndOvertime[] = [
                                                'employee_id' => $employeeId,
                                                // 'overtime_details' => $overtimeResults,
                                                // 'overtime_hours' => $overtimeResults[0]['overtime_hours'],
                                                // // 'start_time' => $overtimeResults[0]['overtime_start_time'],
                                                // // 'end_time' => $overtimeResults[0]['overtime_end_time'],
                                                // 'start_time' => $overtimeResults[0]['check_in_time'],
                                                // 'end_time' => $overtimeResults[0]['check_out_time'],
                                            ];
                                            // }
                                        }
                                    }

                                    // Populate the Repeater with employees
                                    $set('employees_with_month', array_map(function ($employee) use ($state) {
                                        // $options  = [];
                                        // if (strpos($state, '-') !== false) {
                                        //     $yearAndMonthArr = explode('-', $state);
                                        //     $options = [];
                                        //     $year = $yearAndMonthArr[0];
                                        //     $month = $yearAndMonthArr[1];
                                        //     $daysInMonth = Carbon::create($year, $month)->daysInMonth;
                                        //     for ($day = 1; $day <= $daysInMonth; $day++) {
                                        //         $date = Carbon::create($year, $month, $day)->format('Y-m-d');
                                        //         $options[$date] = $date;
                                        //     }
                                        // }
                                        return [
                                            'employee_id' => $employee['employee_id'],

                                            //     return $options;
                                            'dates'       => [],
                                            // 'start_time' => $employee['start_time'],
                                            // 'end_time' => $employee['end_time'],

                                            // 'notes' => null,
                                            // 'hours' => $employee['overtime_hours'],
                                        ];
                                    }, $employeesWithWeekEndOvertime));
                                }),

                        ]),

                    Repeater::make('employees')
                        ->label('')->columnSpanFull()
                        ->visible(fn(Get $get) => in_array($get('type'), [EmployeeOvertime::TYPE_BASED_ON_DAY])
                            && ! is_null($get('branch_id')))
                        ->required()
                        ->columnSpanFull()
                        ->schema(

                            [
                                Grid::make()->columns(4)->schema([
                                    Select::make('employee_id')->live()
                                        ->unique(
                                            ignoreRecord: true,
                                            modifyRuleUsing: function (Unique $rule, Get $get, $state) {
                                                return $rule->where('employee_id', $state)
                                                    ->where('date', $get('../../date'))
                                                ;
                                            }
                                        )
                                        ->relationship('employee', 'name')
                                        ->validationMessages([
                                            'unique' => 'This overtime has been recorded',
                                        ])
                                        ->required(),
                                    TimePicker::make('start_time')->disabled()
                                        ->dehydrated()
                                        ->label('Checkin')
                                        ->live()
                                        ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                            $end   = Carbon::parse($get('end_time'));
                                            $start = Carbon::parse($state); // Parse the start time

                                            // Calculate the difference in hours
                                            $hours = round($start->diffInHours($end), 1);

                                            // Set the result in the hours_as_default field
                                            $set('hours', $hours);
                                        }),

                                    TimePicker::make('end_time')->disabled()
                                        ->dehydrated()
                                        ->label('Checkout')
                                        ->live()
                                        ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                            $start = Carbon::parse($get('start_time'));
                                            $end   = Carbon::parse($state); // Parse the start time

                                            // Calculate the difference in hours
                                            $hours = round($start->diffInHours($end), 1);

                                            // Set the result in the hours_as_default field
                                            $set('hours', $hours);
                                        }),
                                    TextInput::make('hours')->label('Overtime Hours')->numeric()->required()->minValue(0.5),
                                ]),
                                Grid::make()->columns(2)->schema([
                                    TextInput::make('notes')
                                        ->label('Notes')->columnSpanFull()
                                        ->nullable(),
                                ]),
                            ]
                        ),
                    Repeater::make('employees_with_month')
                        ->label('')
                        ->visible(fn(Get $get) => in_array($get('type'), [EmployeeOvertime::TYPE_BASED_ON_MONTH])
                            && ! is_null($get('branch_id')))
                        ->required()
                        ->columnSpanFull()
                        ->schema(

                            [
                                Grid::make()
                                    ->columns(4)->schema([
                                        Select::make('employee_id')->live()
                                            ->columnSpan(2)
                                            ->unique(
                                                ignoreRecord: true,
                                                modifyRuleUsing: function (Unique $rule, Get $get, $state) {
                                                    return $rule->where('employee_id', $state)
                                                        ->where('date', $get('../../date'))
                                                    ;
                                                }
                                            )
                                            ->relationship('employee', 'name')
                                            ->validationMessages([
                                                'unique' => 'This overtime has been recorded',
                                            ])
                                            ->required(),
                                        select::make('dates')->multiple()->label('Dates')
                                            ->columnSpan(2)

                                            ->options(function () {

                                                $options     = [];
                                                $year        = 2024;
                                                $month       = 12;
                                                $daysInMonth = Carbon::create($year, $month)->daysInMonth;
                                                for ($day = 1; $day <= $daysInMonth; $day++) {
                                                    $date           = Carbon::create($year, $month, $day)->format('Y-m-d');
                                                    $options[$date] = $date;
                                                }
                                                return $options;
                                            })
                                            ->live()
                                            ->afterStateUpdated(function ($get, $set, $state) {
                                                foreach ($state as $date) {
                                                    $set('attendances_dates', array_map(function ($date) use ($get, $set) {
                                                        $employeeId     = $get('employee_id');
                                                        $attendanceData = employeeAttendances($employeeId, $date, $date);
                                                        $approvedTime   = array_values($attendanceData)[0]['periods'][0]['attendances']['checkout']['lastcheckout']['approved_overtime'] ?? null;
                                                        // $set('total_hours', $approvedTime);
                                                        return [
                                                            'attendance_date' => $date,

                                                            'total_hours'     => $approvedTime,

                                                        ];
                                                    }, $state));
                                                }
                                            })->maxItems(4),
                                        Repeater::make('attendances_dates')
                                            ->label('')
                                            ->addable(false)
                                            ->minItems(1)->deletable()
                                            ->defaultItems(4)
                                            ->columnSpan(4)->grid(2)
                                            ->schema([
                                                Grid::make()->columns(4)->schema([
                                                    DatePicker::make('attendance_date')
                                                        ->label('Date')
                                                        ->required()
                                                        ->live()
                                                        ->afterStateUpdated(function ($get, $set, $state) {
                                                            $employeeId     = $get('../../employee_id');
                                                            $attendanceData = employeeAttendances($employeeId, $state, $state);
                                                            $approvedTime   = array_values($attendanceData)[0]['periods'][0]['attendances']['checkout']['lastcheckout']['approved_overtime'] ?? null;
                                                            $set('total_hours', $approvedTime);
                                                        })
                                                        ->disabled()
                                                        ->dehydrated(),
                                                    TextInput::make('total_hours')
                                                        ->label('Total Hours')
                                                        // ->numeric()
                                                        ->required()
                                                    // ->minValue(0.5)
                                                    // ->disabled()
                                                    // ->dehydrated()
                                                    ,
                                                ]),
                                            ]),

                                    ]),
                                Grid::make()->columns(2)->schema([
                                    TextInput::make('notes')
                                        ->label('Notes')->columnSpanFull()
                                        ->nullable(),
                                ]),
                            ]
                        ),

                ]
            );
    }
}
