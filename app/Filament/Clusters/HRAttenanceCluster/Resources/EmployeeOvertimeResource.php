<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources;

use App\Filament\Clusters\HRAttenanceCluster;
use App\Filament\Clusters\HRAttenanceCluster\Resources\EmployeeOvertimeResource\Pages;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeOvertime;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EmployeeOvertimeResource extends Resource
{
    protected static ?string $model = EmployeeOvertime::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = HRAttenanceCluster::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Start;
    protected static ?int $navigationSort = 10;
    public static function form(Form $form): Form
    {
        return $form
            ->schema(
                [

                    Fieldset::make()
                        ->columns(3)
                        ->label('Set branch employees & the Date')
                        ->schema([

                            Select::make('branch')->label('Branch')
                                ->helperText('Select to populate the branch employees')
                                ->required()
                                ->live()
                                ->options(Branch::where('active', 1)->select('name', 'id')->get()->pluck('name', 'id'))
                                ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                    $employees = Employee::where('branch_id', $state)->where('active', 1)->select('name', 'id as employee_id')->get()->toArray();

                                    // Populate the Repeater with employees
                                    $set('employees', array_map(function ($employee) {
                                        return [
                                            'employee_id' => $employee['employee_id'],
                                            'start_time' => null, // Optionally set default start time
                                            'end_time' => null, // Optionally set default end time
                                            'reason' => null,
                                            'notes' => null,
                                        ];
                                    }, $employees));
                                })
                            ,
                            DatePicker::make('date')
                                ->label('Overtime Date')
                                ->default(date('Y-m-d'))
                                ->required(),
                            Toggle::make('show_default_values')->label('Set default values?')
                                ->helperText('Check if you want to set default values')
                                ->live()
                            // ->disabled(fn(Get $get) => is_numeric($get('branch_id')))
                                ->inline(false)->default(0),
                        ]),
                    Fieldset::make('default_values')->label('Set default values')
                        ->visible(fn(Get $get) => $get('show_default_values'))
                        ->schema([
                            Grid::make()->columns(3)->schema([
                                TimePicker::make('start_time_as_default')
                                    ->label('Start Time')
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                        // Get the current repeater data
                                        $employees = $get('employees') ?? [];
                                        // dd($employees,$state);
                                        // Loop through the repeater items and set the 'start_time' for each

                                        $end = Carbon::parse($get('end_time_as_default')); // Parse the end time
                                        if (isset($end)) {
                                            $start = Carbon::parse($state); // Parse the start time

                                            // Calculate the difference in hours
                                            $hours = round($start->diffInHours($end), 1);

                                            // Set the result in the hours_as_default field
                                            $set('hours_as_default', $hours);

                                        }

                                        foreach ($employees as $index => $employee) {
                                            $employees[$index]['start_time'] = $state;
                                            $employees[$index]['hours'] = $hours;
                                        }

                                        // Set the updated repeater data back to the 'employees' field
                                        $set('employees', $employees);

                                    })
                                ,

                                TimePicker::make('end_time_as_default')
                                    ->label('End Time')
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                        // Get the current repeater data
                                        $employees = $get('employees') ?? [];
                                        // dd($employees,$state);
                                        // Loop through the repeater items and set the 'end_time' for each

                                        $start = Carbon::parse($get('start_time_as_default')); // Parse the start time
                                        if (isset($start)) {
                                            $end = Carbon::parse($state); // Parse the end time

                                            // Calculate the difference in hours

                                            $hours = round($start->diffInHours($end), 1);

                                            // Set the result in the hours_as_default field
                                            $set('hours_as_default', $hours);
                                        }

                                        foreach ($employees as $index => $employee) {
                                            $employees[$index]['end_time'] = $state;
                                            $employees[$index]['hours'] = $hours;
                                        }

                                        // Set the updated repeater data back to the 'employees' field
                                        $set('employees', $employees);

                                    })
                                ,
                                TextInput::make('hours_as_default')->label('Hours count'),
                            ]),
                            Grid::make()->columns(2)->schema([
                                TextInput::make('reason_as_default')
                                    ->label('Reason')
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                        // Get the current repeater data
                                        $employees = $get('employees') ?? [];
                                        // dd($employees,$state);
                                        // Loop through the repeater items and set the 'reason' for each
                                        foreach ($employees as $index => $employee) {
                                            $employees[$index]['reason'] = $state;
                                        }

                                        // Set the updated repeater data back to the 'employees' field
                                        $set('employees', $employees);
                                    })
                                    ->nullable(),
                                TextInput::make('notes_as_default')
                                    ->label('Notes')
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                        // Get the current repeater data
                                        $employees = $get('employees') ?? [];
                                        // dd($employees,$state);
                                        // Loop through the repeater items and set the 'notes' for each
                                        foreach ($employees as $index => $employee) {
                                            $employees[$index]['notes'] = $state;
                                        }

                                        // Set the updated repeater data back to the 'employees' field
                                        $set('employees', $employees);
                                    })
                                    ->nullable(),
                            ]),
                        ]),
                    Repeater::make('employees')
                        ->label('')
                        ->columnSpanFull()
                        ->schema(

                            [
                                Grid::make()->columns(4)->schema([
                                    Select::make('employee_id')
                                        ->relationship('employee', 'name')
                                        ->required(),
                                    TimePicker::make('start_time')
                                        ->label('Start Time')
                                        ->live()
                                        ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                            $end = Carbon::parse($get('end_time'));
                                            $start = Carbon::parse($state); // Parse the start time

                                            // Calculate the difference in hours
                                            $hours = round($start->diffInHours($end), 1);

                                            // Set the result in the hours_as_default field
                                            $set('hours', $hours);
                                        })
                                        ->required(),

                                    TimePicker::make('end_time')
                                        ->label('End Time')
                                        ->live()
                                        ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                            $start = Carbon::parse($get('start_time'));
                                            $end = Carbon::parse($state); // Parse the start time

                                            // Calculate the difference in hours
                                            $hours = round($start->diffInHours($end), 1);

                                            // Set the result in the hours_as_default field
                                            $set('hours', $hours);
                                        })
                                        ->required(),
                                    TextInput::make('hours')->label('Hours count'),
                                ]),
                                Grid::make()->columns(2)->schema([
                                    TextInput::make('reason')
                                        ->label('Reason')
                                        ->nullable(),
                                    TextInput::make('notes')
                                        ->label('Notes')
                                        ->nullable(),
                                ]),
                            ])
                    // ->default(function (Get $get) {
                    //     $employees = [];
                    //     // dd($get,$get('branch_id'),is_null($get('branch_id')));
                    //     if (!is_null($get('branch_id'))) {
                    //         $employees = Employee::where('branch_id', )->where('active', 1)->select('name', 'id as employee_id')->get()->toArray();
                    //     }

                    //     return $employees;
                    // })

                    ,
                ]
            )
            // ->schema([
            //     $d,

            //     // DatePicker::make('date')
            //     //     ->label('Overtime Date')
            //     //     ->required(),

            //     // Select::make('employee_id')
            //     //     ->relationship('employee', 'name')
            //     //     ->required(),
            //     // TimePicker::make('start_time')
            //     //     ->label('Start Time')
            //     //     ->required(),

            //     // TimePicker::make('end_time')
            //     //     ->label('End Time')
            //     //     ->required(),
            //     // TextInput::make('reason')
            //     //     ->label('Reason')
            //     //     ->nullable(),
            //     // TextInput::make('notes')
            //     //     ->label('Notes')
            //     //     ->nullable(),

            //     // TextInput::make('hours')
            //     //     ->label('Total Hours')
            //     //     ->numeric()
            //     //     ->required(),

            //     // TextInput::make('rate')
            //     //     ->label('Rate')
            //     //     ->numeric()
            //     //     ->nullable(),

            // ])
        ;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.name')
                    ->label('Employee')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('date')
                    ->label('Date')
                    ->sortable()
                    ->date(),

                TextColumn::make('start_time')
                    ->label('Start Time')
                    ->sortable(),

                TextColumn::make('end_time')
                    ->label('End Time')
                    ->sortable(),

                TextColumn::make('hours')
                    ->label('Hours')
                    ->sortable(),

            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployeeOvertimes::route('/'),
            'create' => Pages\CreateEmployeeOvertime::route('/create'),
            'edit' => Pages\EditEmployeeOvertime::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

}
