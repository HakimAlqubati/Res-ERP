<?php

namespace App\Filament\Clusters\HRApplicationsCluster\Resources\EmployeeApplicationResource\Form;


use App\Models\Employee;
use App\Models\EmployeeApplicationV2;
use DateTime;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ToggleButtons;

use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use App\Filament\Clusters\HRApplicationsCluster\Resources\EmployeeApplicationResource;

class EmployeeApplicationForm
{
    public static function configure($schema)
    {
        return $schema
            ->components([
                Fieldset::make()->columnSpanFull()->label('')->columns(2)->schema([
                    Select::make('employee_id')
                        ->label(__('lang.employee'))
                        ->searchable()
                        ->required()
                        ->live()
                        // ->afterStateUpdated(function ($get, $set, $state) {
                        //     $employee = Employee::find($state);
                        //     $set('basic_salary', $employee?->salary);
                        // })
                        ->disabled(function () {
                            if (isStuff() || isFinanceManager()) {
                                return true;
                            }
                            return false;
                        })
                        ->default(function () {
                            if (isStuff() || isFinanceManager()) {
                                return auth()->user()->employee->id;
                            }
                        })
                        ->options(Employee::select('name', 'id')
                            ->active()->forBranchManager()
                            ->get()->plucK('name', 'id')),

                    DatePicker::make('application_date')
                        ->label(__('lang.request_date'))
                        ->default(date('Y-m-d'))
                        ->live()
                        ->disabled()
                        ->dehydrated()
                        ->afterStateUpdated(function ($set, $get, $state) {
                            // Create a DateTime object
                            $dateTime = new DateTime($state);

                            // Get the year and month
                            $year  = $dateTime->format('Y'); // Year (e.g., 2024)
                            $month = $dateTime->format('m'); // Month (e.g., 12)

                            $set('leaveRequest.detail_year', $year);
                            $set('leaveRequest.detail_month', $month);
                            $set('leaveRequest.detail_from_date', $get('application_date'));
                            $set('missedCheckinRequest.date', $get('application_date'));
                            $set('missedCheckoutRequest.detail_date', $get('application_date'));
                            $set('leaveRequest.detail_to_date', $get('application_date'));
                            $set('leaveRequest.detail_days_count', 1);
                        })
                        ->required(),

                    ToggleButtons::make('application_type_id')
                        ->columnSpan(2)
                        ->label(__('lang.request_type'))
                        ->hiddenOn('edit')
                        ->live()->required()
                        ->options(EmployeeApplicationV2::APPLICATION_TYPES)
                        ->icons([
                            EmployeeApplicationV2::APPLICATION_TYPE_ADVANCE_REQUEST                => 'heroicon-o-banknotes',
                            EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST                  => 'heroicon-o-clock',
                            EmployeeApplicationV2::APPLICATION_TYPE_ATTENDANCE_FINGERPRINT_REQUEST => 'heroicon-o-finger-print',
                            EmployeeApplicationV2::APPLICATION_TYPE_DEPARTURE_FINGERPRINT_REQUEST  => 'heroicon-o-finger-print',
                        ])->inline()
                        ->colors([
                            EmployeeApplicationV2::APPLICATION_TYPE_DEPARTURE_FINGERPRINT_REQUEST  => 'info',
                            EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST                  => 'warning',
                            EmployeeApplicationV2::APPLICATION_TYPE_ATTENDANCE_FINGERPRINT_REQUEST => 'success',
                            EmployeeApplicationV2::APPLICATION_TYPE_ADVANCE_REQUEST                => 'danger',
                        ])
                        ->afterStateUpdated(function ($set, $get) {
                            // Create a DateTime object
                            $dateTime = new DateTime($get('application_date'));

                            // Get the year and month
                            $year  = $dateTime->format('Y'); // Year (e.g., 2024)
                            $month = $dateTime->format('m'); // Month (e.g., 12)

                            $set('leaveRequest.detail_year', $year);
                            $set('leaveRequest.detail_month', $month);
                            $set('leaveRequest.detail_from_date', $get('application_date'));
                            $set('leaveRequest.detail_to_date', $get('application_date'));
                            $set('leaveRequest.detail_days_count', 1);
                            $set('missedCheckinRequest.date', $get('application_date'));
                            $set('missedCheckoutRequest.detail_date', $get('application_date'));
                            $set('missedCheckinRequest.time', now()->toTimeString());
                            $set('missedCheckoutRequest.detail_time', now()->toTimeString());
                        }),
                ]),
                Fieldset::make('')->columnSpanFull()
                    ->label(fn(Get $get): string => EmployeeApplicationV2::APPLICATION_TYPES[$get('application_type_id')])

                    ->columns(1)
                    ->visible(fn(Get $get): bool => is_numeric($get('application_type_id')))

                    ->schema(function ($get, $set) {

                        $schema = [];
                        if (
                            $get('application_type_id') == EmployeeApplicationV2::APPLICATION_TYPE_ATTENDANCE_FINGERPRINT_REQUEST
                        ) {
                            return EmployeeApplicationResource::attendanceRequestForm();
                        }
                        if (
                            $get('application_type_id') == EmployeeApplicationV2::APPLICATION_TYPE_DEPARTURE_FINGERPRINT_REQUEST
                        ) {
                            return EmployeeApplicationResource::departureRequestForm($set, $get);
                        }
                        if ($get('application_type_id') == EmployeeApplicationV2::APPLICATION_TYPE_ADVANCE_REQUEST) {
                            return EmployeeApplicationResource::advanceRequestForm($set, $get);
                        }
                        if ($get('application_type_id') == EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST) {
                            return EmployeeApplicationResource::leaveRequestForm($set, $get);
                        }

                        return [
                            Fieldset::make()->columns(count($schema))->schema(
                                $schema
                            ),
                        ];
                    }),
                Fieldset::make()->columnSpanFull()->label('')->schema([
                    Textarea::make('notes') // Add the new details field
                        ->label(__('lang.notes'))
                        ->placeholder(__('lang.notes') . '...')
                        // ->rows(5)
                        ->columnSpanFull(),
                ]),
            ]);
    }
}
