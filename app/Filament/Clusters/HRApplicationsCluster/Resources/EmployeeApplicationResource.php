<?php

namespace App\Filament\Clusters\HRApplicationsCluster\Resources;

use App\Filament\Clusters\HRApplicationsCluster;
use App\Filament\Clusters\HRApplicationsCluster\Resources\EmployeeApplicationResource\Form\EmployeeApplicationForm;
use App\Filament\Clusters\HRApplicationsCluster\Resources\EmployeeApplicationResource\Pages;
use App\Filament\Clusters\HRApplicationsCluster\Resources\EmployeeApplicationResource\Table\EmployeeApplicationTable;
use App\Filament\Pages\AttendanecEmployee2 as AttendanecEmployee;
use App\Models\AdvanceRequest;
use App\Models\ApplicationTransaction;
use App\Models\AppLog;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeApplication;
use App\Models\EmployeeApplicationV2;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Services\HR\Attendance\AttendanceService;
use App\Services\HR\MonthClosure\MonthClosureService;
use Carbon\Carbon;
use DateTime;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Filters\TrashedFilter;

class EmployeeApplicationResource extends Resource
{
    protected static ?string $model = EmployeeApplicationV2::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::PencilSquare;
    protected ?bool $hasDatabaseTransactions = true;

    // protected static ?string $cluster                             = HRApplicationsCluster::class;
    // protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    // protected static ?int $navigationSort                         = 0;
    protected static ?string $label                               = 'Request';
    protected static ?string $pluralLabel                         = 'Requests';

    protected static ?string $pluralModelLabel = 'Requests';

    public static function form(Schema $schema): Schema
    {
        return EmployeeApplicationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmployeeApplicationTable::configure($table);
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
            'index'  => Pages\ListEmployeeApplications::route('/'),
            'create' => Pages\CreateEmployeeApplication::route('/create'),
            // 'edit' => Pages\EditEmployeeApplicationV2::route('/{record}/edit'),
        ];
    }

    public static function getDetailsKeysAndValues(array $data)
    {
        // Use array_filter to get the keys starting with 'requr_pattern_'
        $filteredData = array_filter($data, function ($value, $key) {
            return Str::startsWith($key, 'detail_');
        }, ARRAY_FILTER_USE_BOTH);

        return $filteredData;
    }

    public static function getTitleCasePluralModelLabel(): string
    {
        return 'Requests';
    }

    public static function getTitleCaseModelLabel(): string
    {
        return 'Request';
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereHas('employee', function ($q) {
            $q->whereNull('deleted_at'); // ignore soft-deleted employees
        })->count();
    }

    public static function canCreate(): bool
    {
        return true;
        return static::can('create');
    }

    public static function canViewAny(): bool
    {
        if (isSystemManager() || isSuperAdmin() || isBranchManager() || isStuff() || isFinanceManager()) {
            return true;
        }
        return false;
    }
    public static function approveDepartureRequest(): Action
    {
        return Action::make('approveDepartureRequest')
            ->label('Approve')
            ->button()
            ->visible(
                fn($record): bool =>
                $record->status == EmployeeApplicationV2::STATUS_PENDING
                    && $record->application_type_id == EmployeeApplicationV2::APPLICATION_TYPE_DEPARTURE_FINGERPRINT_REQUEST
            )

            // ✅ ملاحظة (Note) تظهر كـ Tooltip عندما يكون الزر Disabled
            ->tooltip(function ($record) {
                $hasCheckin = Attendance::query()
                    ->where('employee_id', $record?->employee_id)
                    ->where('check_date', $record?->detail_date)
                    ->where('check_type', Attendance::CHECKTYPE_CHECKIN)
                    ->exists();

                return $hasCheckin
                    ? null
                    : 'There is no check-in, so you cannot approve this request.';
            })

            // ✅ تعطيل زر الـ Action في حالة عدم وجود Check-in
            ->disabled(function ($record): bool {
                return ! Attendance::query()
                    ->where('employee_id', $record?->employee_id)
                    ->where('check_date', $record?->detail_date)
                    ->where('check_type', Attendance::CHECKTYPE_CHECKIN)
                    ->exists();
            })

            ->color('success')
            ->icon('heroicon-o-check')
            ->databaseTransaction()
            ->action(function ($record, $data) {
                DB::beginTransaction();
                try {
                    $employee = $record->employee;

                    // باقي الكود كما هو...
                    $validated = [
                        'employee_id'      => $employee->id,
                        'date_time'        => $data['request_check_date'] . ' ' . $data['request_check_time'],
                        'type'             => Attendance::CHECKTYPE_CHECKOUT,
                        'attendance_type'  => Attendance::ATTENDANCE_TYPE_REQUEST,
                    ];

                    $result = app(AttendanceService::class)->handle($validated);

                    if ($result) {
                        $record->update([
                            'status'      => EmployeeApplicationV2::STATUS_APPROVED,
                            'approved_by' => auth()->user()->id,
                            'approved_at' => now(),
                        ]);
                        DB::commit();
                        showSuccessNotifiMessage('Done');
                    } else {
                        showWarningNotifiMessage('Faild');
                    }
                } catch (Exception $e) {
                    DB::rollBack();
                    AppLog::write('Error approving attendance request: ' . $e->getMessage(), AppLog::LEVEL_ERROR);
                    return Notification::make()->warning()->body($e->getMessage())->send();
                }
            })
            ->schema(function ($record) {
                $attendance = Attendance::where('employee_id', $record?->employee_id)
                    ->where('check_date', $record?->detail_date)
                    ->where('check_type', Attendance::CHECKTYPE_CHECKIN)
                    ->first();

                return [
                    Fieldset::make()
                        ->disabled()
                        ->label('Attendance data')
                        ->columns(3)
                        ->schema([
                            TextInput::make('employee')->default($record?->employee?->name),
                            DatePicker::make('check_date')->default($attendance?->check_date),
                            TimePicker::make('check_time')->default($attendance?->check_time),
                            TextInput::make('period_title')->label('Period')->default($attendance?->period?->name),
                            TextInput::make('start_at')->default($attendance?->period?->start_at),
                            TextInput::make('end_at')->default($attendance?->period?->end_at),
                            Hidden::make('period')->default($attendance?->period),
                        ]),

                    Fieldset::make()
                        ->disabled(false)
                        ->label('Request data')
                        ->columns(2)
                        ->schema([
                            DatePicker::make('request_check_date')
                                ->default($record?->missedCheckoutRequest?->date)
                                ->label('Date'),
                            TimePicker::make('request_check_time')
                                ->default($record?->missedCheckoutRequest?->time)
                                ->label('Time'),
                        ]),
                ];
            });
    }


    // public static function approveDepartureRequest(): Action
    // {
    //     return Action::make('approveDepartureRequest')->label('Approve')->button()
    //         ->visible(fn($record): bool => ($record->status == EmployeeApplicationV2::STATUS_PENDING && $record->application_type_id == EmployeeApplicationV2::APPLICATION_TYPE_DEPARTURE_FINGERPRINT_REQUEST))
    //         ->color('success')
    //         ->icon('heroicon-o-check')
    //         ->databaseTransaction()
    //         ->action(function ($record, $data) {
    //             DB::beginTransaction();
    //             try {
    //                 $employee = $record->employee;
    //                 $data['request_check_date'];
    //                 $data['request_check_time'];
    //                 $validated = [
    //                     'employee_id' => $employee->id,
    //                     'date_time' => $data['request_check_date'] . ' ' . $data['request_check_time'],
    //                     'type' => Attendance::CHECKTYPE_CHECKOUT,
    //                     'attendance_type' => Attendance::ATTENDANCE_TYPE_REQUEST
    //                 ];
    //                 $result = app(AttendanceService::class)->handle($validated);
    //                 // dd($result);
    //                 if ($result) {
    //                     $record->update([
    //                         'status'      => EmployeeApplicationV2::STATUS_APPROVED,
    //                         'approved_by' => auth()->user()->id,
    //                         'approved_at' => now(),
    //                     ]);
    //                     DB::commit();
    //                     showSuccessNotifiMessage('Done');
    //                 } else {
    //                     showWarningNotifiMessage('Faild');
    //                 }
    //             } catch (Exception $e) {
    //                 DB::rollBack();
    //                 AppLog::write('Error approving attendance request: ' . $e->getMessage(), AppLog::LEVEL_ERROR);
    //                 return Notification::make()->warning()->body($e->getMessage())->send();
    //                 // Handle the exception (log it, return an error message, etc.)
    //                 // Optionally, you could return a user-friendly error message
    //                 throw new Exception($e->getMessage());
    //                 throw new Exception('There was an error processing the attendance request. Please try again later.');
    //             }
    //         })
    //         // ->disabledForm()
    //         ->schema(function ($record) {

    //             $attendance = Attendance::where('employee_id', $record?->employee_id)
    //                 ->where('check_date', $record?->detail_date)
    //                 ->where('check_type', Attendance::CHECKTYPE_CHECKIN)
    //                 ->first();

    //             return [
    //                 Fieldset::make()->disabled()->label('Attendance data')->columns(3)->schema([
    //                     TextInput::make('employee')->default($record?->employee?->name),
    //                     DatePicker::make('check_date')->default($attendance?->check_date),
    //                     TimePicker::make('check_time')->default($attendance?->check_time),
    //                     TextInput::make('period_title')->label('Period')->default($attendance?->period?->name),
    //                     TextInput::make('start_at')->default($attendance?->period?->start_at),
    //                     TextInput::make('end_at')->default($attendance?->period?->end_at),
    //                     Hidden::make('period')->default($attendance?->period),
    //                 ]),
    //                 Fieldset::make()->disabled(false)->label('Request data')->columns(2)->schema([
    //                     DatePicker::make('request_check_date')->default($record?->missedCheckoutRequest?->date)->label('Date'),
    //                     TimePicker::make('request_check_time')->default($record?->missedCheckoutRequest?->time)->label('Time'),
    //                 ]),

    //             ];
    //         })
    //     ;
    // }

    public static function rejectDepartureRequest(): Action
    {
        return Action::make('rejectDepartureRequest')->label('Reject')->button()
            ->visible(fn($record): bool => ($record->status == EmployeeApplicationV2::STATUS_PENDING && $record->application_type_id == EmployeeApplicationV2::APPLICATION_TYPE_DEPARTURE_FINGERPRINT_REQUEST))
            ->color('danger')
            ->icon('heroicon-o-x-mark')
            ->action(function ($record) {
                $record->update([
                    'status'      => EmployeeApplicationV2::STATUS_REJECTED,
                    'rejected_by' => auth()->user()->id,
                    'rejected_at' => now(),
                ]);
            })
            // ->disabledForm()
            ->schema(function ($record) {
                return [
                    Textarea::make('rejected_reason')->label('Reason for Rejection')->placeholder('Please provide a reason...')->required(),
                ];
            });
    }
    public static function approveAdvanceRequest(): Action
    {
        return Action::make('approveAdvanceRequest')->label('Approve')->button()
            ->visible(fn($record): bool => ($record->status == EmployeeApplicationV2::STATUS_PENDING && $record->application_type_id == EmployeeApplicationV2::APPLICATION_TYPE_ADVANCE_REQUEST))
            ->color('success')
            ->icon('heroicon-o-check')

            ->action(function ($record) {
                DB::beginTransaction();
                try {
                    $adv = $record->advanceRequest;

                    // Guards
                    if (! $adv || ! $record->employee_id || ! $adv->advance_amount || ! $adv->number_of_months_of_deduction || ! $adv->deduction_starts_from) {
                        Notification::make()->danger()->title('Missing advance data.')->send();
                        DB::rollBack();
                        return;
                    }

                    // Prevent duplicates for this application
                    if (\App\Models\EmployeeAdvanceInstallment::where('application_id', $record->id)->exists()) {
                        Notification::make()->warning()->title('Installments already exist.')->send();
                        DB::rollBack();
                        return;
                    }

                    // Approve
                    $record->update([
                        'status'      => \App\Models\EmployeeApplicationV2::STATUS_APPROVED,
                        'approved_by' => auth()->id(),
                        'approved_at' => now(),
                    ]);

                    // Normalize start month
                    $startMonth = \Carbon\Carbon::parse($adv->deduction_starts_from)->startOfMonth()->toDateString();

                    // Generate installments (creates sequence + status=scheduled)
                    \App\Models\AdvanceRequest::createInstallments(
                        $record->employee_id,
                        $adv->advance_amount,
                        $adv->number_of_months_of_deduction,
                        $startMonth,
                        $record->id // application_id
                    );

                    // Recompute aggregates on the advance request
                    // (use model method if موجود، وإلا fallback سريع)
                    if (method_exists($adv, 'recomputeTotals')) {
                        $adv->refresh();
                        $adv->recomputeTotals();
                    } else {
                        $sumAll  = (float) \App\Models\EmployeeAdvanceInstallment::where('application_id', $record->id)->sum('installment_amount');
                        $sumPaid = (float) \App\Models\EmployeeAdvanceInstallment::where('application_id', $record->id)->where('is_paid', true)->sum('installment_amount');
                        $cntPaid =        \App\Models\EmployeeAdvanceInstallment::where('application_id', $record->id)->where('is_paid', true)->count();
                        $lastDue =        \App\Models\EmployeeAdvanceInstallment::where('application_id', $record->id)->max('due_date');

                        $adv->remaining_total   = round($sumAll - $sumPaid, 2);
                        $adv->paid_installments = $cntPaid;
                        if ($lastDue) $adv->deduction_ends_at = $lastDue;
                        $adv->saveQuietly();
                    }

                    DB::commit();
                    Notification::make()->success()->title('Approved and installments created.')->send();
                } catch (\Throwable $th) {
                    DB::rollBack();
                    Notification::make()->danger()->title('Approval error.')->send();
                    throw $th;
                }
            })


            ->disabledForm()
            ->schema(function ($record) {

                $details = $record->advanceRequest;

                $detailDate             = $details?->date;
                $monthlyDeductionAmount = $details?->monthly_deduction_amount;
                $advanceAmount          = $details->advance_amount;

                $deductionStartsFrom       = $details?->deduction_starts_from;
                $deductionEndsAt           = $details?->deduction_ends_at;
                $numberOfMonthsOfDeduction = $details?->number_of_months_of_deduction;
                $notes                     = $record?->notes;
                return [
                    Fieldset::make()->label('Request data')->columns(3)->schema([
                        TextInput::make('employee')->default($record?->employee?->name),
                        DatePicker::make('date')->default($detailDate)->label('Advance date'),
                        TextInput::make('advance_amount')->default($advanceAmount),
                        TextInput::make('deductionStartsFrom')->label('Deducation starts from')->default($deductionStartsFrom),
                        TextInput::make('deductionEndsAt')->label('Deducation ends at')->default($deductionEndsAt),
                        TextInput::make('numberOfMonthsOfDeduction')->label('Number of months of deduction')->default($numberOfMonthsOfDeduction),
                        TextInput::make('monthlyDeductionAmount')->label('Monthly deduction amount')->default($monthlyDeductionAmount),

                    ]),
                    Fieldset::make()->label('Notes')->columns(2)->schema([
                        TextInput::make('test')->label('Notes')->columnSpanFull()->default($notes),
                    ]),
                ];
            })
        ;
    }

    public static function rejectAdvanceRequest(): Action
    {
        return Action::make('rejectAdvanceRequest')->label('Reject')->button()
            ->visible(fn($record): bool => ($record->status == EmployeeApplicationV2::STATUS_PENDING && $record->application_type_id == EmployeeApplicationV2::APPLICATION_TYPE_ADVANCE_REQUEST))
            ->color('danger')
            ->icon('heroicon-o-x-mark')
            ->action(function ($record) {
                $record->update([
                    'status'      => EmployeeApplicationV2::STATUS_REJECTED,
                    'rejected_by' => auth()->user()->id,
                    'rejected_at' => now(),
                ]);
            })
            // ->disabledForm()
            ->schema(function ($record) {
                return [
                    Textarea::make('rejected_reason')->label('Reason for Rejection')->placeholder('Please provide a reason...')->required(),
                ];
            });
    }

    public static function approveLeaveRequest(): Action
    {
        return Action::make('approveLeaveRequest')->label('Approve')->button()
            ->visible(fn($record): bool => ($record->status == EmployeeApplicationV2::STATUS_PENDING && $record->application_type_id == EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST))
            ->color('success')
            ->icon('heroicon-o-check')
            ->action(function ($record, $data) {

                // dd($record, $data);
                DB::beginTransaction();
                try {
                    $record->update([
                        'status'      => EmployeeApplicationV2::STATUS_APPROVED,
                        'approved_by' => auth()->user()->id,
                        'approved_at' => now(),
                    ]);
                    // Step 3: Calculate the number of leave days and update the leave balance
                    $leaveBalance = LeaveBalance::getLeaveBalanceForEmployee(
                        $record->employee_id,
                        $record->leaveRequest->year,
                        $record->leaveRequest->leave_type,
                        $record->leaveRequest->month
                    );
                    // Update the balance if found
                    if ($leaveBalance) {
                        $leaveBalance->decrement('balance', $record->leaveRequest->days_count);
                        DB::commit();
                        showSuccessNotifiMessage('Done');
                    } else {
                        // showWarningNotifiMessage('dd');
                        throw new Exception('Leave balance not found for the given conditions.', $leaveBalance);
                    }
                } catch (Exception $th) {
                    //throw $th;
                    DB::rollBack();
                    showWarningNotifiMessage('Faild', $th->getMessage());
                }
            })
            ->disabledForm()
            ->schema(function ($record) {
                $leaveRequest = $record?->leaveRequest;
                $leaveTypeId  = $leaveRequest->leave_type;

                $toDate    = $leaveRequest->end_date;
                $fromDate  = $leaveRequest->start_date;
                $daysCount = $leaveRequest->days_count;
                $year      = $leaveRequest->year;
                $month     = getMonthArrayWithKeys()[$leaveRequest->month] ?? '';
                $leaveType = LeaveType::find($leaveTypeId)->name;

                return [
                    Fieldset::make()->label('Request data')->columns(3)->schema([
                        TextInput::make('employee')->default($record?->employee?->name),
                        TextInput::make('leave')->default($leaveType),
                        DatePicker::make('from_date')->default($fromDate)->label('From date'),
                        DatePicker::make('to_date')->default($toDate)->label('To date'),
                        TextInput::make('detail_year')->default($year)->label('Year'),
                        TextInput::make('detail_month')->default($month)->label('Month'),
                        TextInput::make('days_count')->default($daysCount),
                    ]),
                ];
            })
        ;
    }

    public static function rejectLeaveRequest(): Action
    {
        return Action::make('rejectLeaveRequest')->label('Reject')->button()
            ->visible(fn($record): bool => ($record->status == EmployeeApplicationV2::STATUS_PENDING && $record->application_type_id == EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST))
            ->color('danger')
            ->icon('heroicon-o-x-mark')
            ->action(function ($record) {
                $record->update([
                    'status'      => EmployeeApplicationV2::STATUS_REJECTED,
                    'rejected_by' => auth()->user()->id,
                    'rejected_at' => now(),
                ]);
            })
            // ->disabledForm()
            ->schema(function ($record) {
                return [
                    Textarea::make('rejected_reason')->label('Reason for Rejection')->placeholder('Please provide a reason...')->required(),
                ];
            });
    }

    public static function approveAttendanceRequest(): Action
    {
        return Action::make('approveAttendanceRequest')->label('Approve')->button()
            ->visible(fn($record): bool => ($record->status == EmployeeApplicationV2::STATUS_PENDING && $record->application_type_id == EmployeeApplicationV2::APPLICATION_TYPE_ATTENDANCE_FINGERPRINT_REQUEST))
            ->color('success')
            ->icon('heroicon-o-check')
            ->action(function ($record, $data) {
                // Logic for approving attendance fingerprint requests
                DB::beginTransaction();
                try {

                    $employee = $record->employee;
                    $data['request_check_date'];
                    $data['request_check_time'];
                    $validated = [
                        'employee_id' => $employee->id,
                        'date_time' => $data['request_check_date'] . ' ' . $data['request_check_time'],
                        'type' => Attendance::CHECKTYPE_CHECKIN,
                        'attendance_type' => Attendance::ATTENDANCE_TYPE_REQUEST
                    ];
                    $result = app(AttendanceService::class)->handle($validated);
                    if ($result) {
                        $record->update([
                            'status'      => EmployeeApplicationV2::STATUS_APPROVED,
                            'approved_by' => auth()->user()->id,
                            'approved_at' => now(),
                        ]);
                        DB::commit();
                        showSuccessNotifiMessage('Done');
                    } else {
                        showWarningNotifiMessage('Faild');
                    }
                } catch (Exception $th) {

                    DB::rollBack();
                    showWarningNotifiMessage($th->getMessage());
                    throw $th;
                }
            })
            ->disabledForm()
            ->schema(function ($record) {
                return [
                    Fieldset::make()->label('Request data')->columns(2)->schema([
                        DatePicker::make('request_check_date')->default($record?->missedCheckinRequest?->date)->label('Date'),
                        TimePicker::make('request_check_time')->default($record?->missedCheckinRequest?->time)->label('Time'),
                    ]),
                ];
            });
    }
    public static function departureRequesttDetails(): Action
    {
        return Action::make('departureRequesttDetails')->label('Details')->button()
            ->color('info')
            ->icon('heroicon-m-newspaper')

            ->disabledForm()
            ->form(function ($record) {
                $details    = $record->missedCheckoutRequest;
                $attendance = Attendance::where('employee_id', $record?->employee_id)
                    ->where('check_date', $details->date)
                    ->where('check_type', Attendance::CHECKTYPE_CHECKIN)
                    ->first();

                return [
                    Fieldset::make()->disabled()->label('Attendance data')->columns(3)->schema([
                        TextInput::make('employee')->default($record?->employee?->name),
                        DatePicker::make('check_date')->default($attendance?->check_date),
                        TimePicker::make('check_time')->default($attendance?->check_time),
                        TextInput::make('period_title')->label('Period')->default($attendance?->period?->name),
                        TextInput::make('start_at')->default($attendance?->period?->start_at),
                        TextInput::make('end_at')->default($attendance?->period?->end_at),
                        Hidden::make('period')->default($attendance?->period),
                    ]),
                    Fieldset::make()->disabled(false)->label('Request data')->columns(2)->schema([
                        DatePicker::make('request_check_date')->default($details->date)->label('Date'),
                        TimePicker::make('request_check_time')->default($details->time)->label('Time'),
                    ]),

                ];
            })
            ->modalSubmitAction(false)
            ->modalCancelAction(false)
        ;
    }
    public static function AttendanceRequestDetails(): Action
    {
        return Action::make('AttendanceRequestDetails')->label('Details')->button()
            ->color('info')
            ->icon('heroicon-m-newspaper')

            ->disabledForm()
            ->schema(function ($record) {
                $details = $record->missedCheckinRequest;
                return [
                    Fieldset::make()->label('Request data')->columns(2)->schema([
                        DatePicker::make('request_check_date')->default($details->date)->label('Date'),
                        TimePicker::make('request_check_time')->default($details->time)->label('Time'),
                    ]),
                ];
            })
            ->modalSubmitAction(false)
            ->modalCancelAction(false)
        ;
    }
    public static function LeaveRequesttDetails(): Action
    {
        return Action::make('LeaveRequesttDetails')->label('Details')->button()
            ->color('info')
            ->icon('heroicon-m-newspaper')

            ->disabledSchema()
            ->schema(function ($record) {
                $leaveRequest = $record?->leaveRequest;
                $leaveTypeId  = $leaveRequest->leave_type;

                $toDate    = $leaveRequest->end_date;
                $fromDate  = $leaveRequest->start_date;
                $daysCount = $leaveRequest->days_count;
                $year      = $leaveRequest->year;
                // $month     = getMonthArrayWithKeys()[$leaveRequest->month] ?? '';
                $month     =  $leaveRequest->month  ?? '';
                $leaveType = LeaveType::find($leaveTypeId)->name;

                return [
                    Fieldset::make()->columnSpanFull()->label('Request data')->columns(2)->schema([
                        TextInput::make('employee')->columnSpan(2)->default($record?->employee?->name),
                        TextInput::make('leave')->default($leaveType),
                        TextInput::make('days_count')->default($daysCount),
                        DatePicker::make('from_date')->default($fromDate)->label('From date'),
                        DatePicker::make('to_date')->default($toDate)->label('To date'),
                        TextInput::make('detail_year')->default($year)->label('Year'),
                        TextInput::make('detail_month')->default($month)->label('Month'),
                    ]),
                ];
            })
            // ->modalSubmitAction(false)
            ->modalSubmitAction(false)
            ->modalCancelAction(false)
        ;
    }
    public static function advancedRequestDetails(): Action
    {
        return Action::make('advancedRequestDetails')->label('Details')->button()
            ->color('info')
            ->icon('heroicon-m-newspaper')

            ->disabledForm()
            ->schema(function ($record) {
                $advanceDetails = $record->advanceRequest;
                // dd($record,$advanceDetails);
                $detailDate                = $advanceDetails->date;
                $monthlyDeductionAmount    = $advanceDetails->monthly_deduction_amount;
                $advanceAmount             = $advanceDetails->advance_amount;
                $deductionStartsFrom       = $advanceDetails->deduction_starts_from;
                $deductionEndsAt           = $advanceDetails->deduction_ends_at;
                $numberOfMonthsOfDeduction = $advanceDetails->number_of_months_of_deduction;
                $notes                     = $record?->notes;
                return [
                    Fieldset::make()->label('Request data')->columns(3)->schema([
                        TextInput::make('employee')->default($record?->employee?->name),
                        DatePicker::make('date')->default($detailDate)->label('Advance date'),
                        TextInput::make('advance_amount')->default($advanceAmount),
                        TextInput::make('deductionStartsFrom')->label('Deducation starts from')->default($deductionStartsFrom),
                        TextInput::make('deductionEndsAt')->label('Deducation ends at')->default($deductionEndsAt),
                        TextInput::make('numberOfMonthsOfDeduction')->label('Number of months of deduction')->default($numberOfMonthsOfDeduction),
                        TextInput::make('monthlyDeductionAmount')->label('Monthly deduction amount')->default($monthlyDeductionAmount),

                    ]),
                    Fieldset::make()->label('Notes')->columns(2)->schema([
                        TextInput::make('test')->label('Notes')->columnSpanFull()->default($notes),
                    ]),
                ];
            })
            ->modalSubmitAction(false)
            ->modalCancelAction(false)
        ;
    }

    public static function rejectAttendanceRequest(): Action
    {
        return Action::make('rejectAttendanceRequest')->label('Reject')->button()
            ->visible(fn($record): bool => ($record->status == EmployeeApplicationV2::STATUS_PENDING && $record->application_type_id == EmployeeApplicationV2::APPLICATION_TYPE_ATTENDANCE_FINGERPRINT_REQUEST))
            ->color('danger')
            ->icon('heroicon-o-x-mark')
            ->action(function ($record) {
                $record->update([
                    'status'      => EmployeeApplicationV2::STATUS_REJECTED,
                    'rejected_by' => auth()->user()->id,
                    'rejected_at' => now(),
                ]);
            })
            // ->disabledForm()
            ->schema(function ($record) {
                return [
                    Textarea::make('rejected_reason')->label('Reason for Rejection')->placeholder('Please provide a reason...')->required(),
                ];
            });
    }

    public static function canDelete(Model $record): bool
    {
        if (isSuperAdmin() || isSystemManager()) {
            return true;
        }
        return false;
    }

    public static function canDeleteAny(): bool
    {
        if (isSuperAdmin() || isSystemManager()) {
            return true;
        }
        return false;
    }

    public static function leaveRequestForm($set, $get)
    {
        $leaveBalances = LeaveBalance::where('employee_id', $get('employee_id'))->pluck('leave_type_id');
        $set('from_to_date', date('Y-m-d'));
        // Get the leave types that are active and have a balance for the employee
        $leaveTypes = LeaveType::where('active', 1)
            ->whereIn('id', $leaveBalances)
            ->whereHas('leaveBalances', function ($query) use ($get) {
                $query->where('employee_id', $get('employee_id'))
                    ->where('balance', '>', 0); // Ensure the balance is greater than 0
            })
            ->select('name', 'id')
            ->get()
            ->pluck('name', 'id');
        return [
            Fieldset::make('leaveRequest')
                ->relationship('leaveRequest')->mutateRelationshipDataBeforeCreateUsing(function ($data, $get) {

                    $data['application_type_id']   = 1;
                    $data['application_type_name'] = EmployeeApplicationV2::APPLICATION_TYPE_NAMES[EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST];

                    $data['employee_id'] = $get('employee_id');
                    $data['leave_type']  = $data['detail_leave_type_id'];
                    $data['start_date']  = $data['detail_from_date'];
                    $data['end_date']    = $data['detail_to_date'];

                    $data['year']       = $data['detail_year'];
                    $data['month']      = $data['detail_month'];
                    $data['days_count'] = $data['detail_days_count'];
                    $date               = $get('detail_from_date') ?? now();
                    // app(MonthClosureService::class)->ensureMonthIsOpen($date);
                    return $data;
                })
                ->saveRelationshipsUsing(static function ($record, $state) {
                    $data =  $state;
                    $data['application_type_id']   = EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST;
                    $data['application_type_name'] = \App\Models\EmployeeApplicationV2::APPLICATION_TYPE_NAMES[\App\Models\EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST];
                    $data['employee_id'] = $data['employee_id'] ?? $record->employee_id;
                    $data['leave_type']  = $data['detail_leave_type_id'] ?? null;
                    $data['start_date']  = $data['detail_from_date'] ?? null;
                    $data['end_date']    = $data['detail_to_date'] ?? null;
                    $data['year']        = $data['detail_year'] ?? now()->year;
                    $data['month']       = $data['detail_month'] ?? now()->month;
                    $data['days_count']  = $data['detail_days_count'];
                    return $data;
                })->schema(

                    [
                        Grid::make()->columns(4)->schema([
                            Select::make('detail_leave_type_id')->label('Leave type')
                                ->requiredIf('application_type_id', EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST)
                                ->live()
                                ->options(
                                    $leaveTypes
                                )->required()
                                ->afterStateUpdated(function ($get, Set $set, $state) {
                                    $leaveBalance = LeaveBalance::getLeaveBalanceForEmployee($get('../employee_id'), $get('detail_year'), $state, $get('detail_month'));
                                    $set('detail_balance', $leaveBalance?->balance);
                                }),
                            Select::make('detail_year')->label('Year')

                                ->options([
                                    2024 => 2024,
                                    2025 => 2025,
                                    2026 => 2026,
                                ])->disabled()->dehydrated()
                                ->live(),
                            Select::make('detail_month')->label('Month')
                                ->options(getMonthArrayWithKeys())
                                ->live()
                                ->dehydrated(),
                            TextInput::make('detail_balance')->label('Leave balance')->disabled(),

                        ]),
                        Grid::make()->columns(3)->schema([
                            DatePicker::make('detail_from_date')
                                ->label('From Date')
                                ->reactive()
                                ->default(date('Y-m-d'))
                                ->required()
                                ->dehydrated()
                                ->afterStateUpdated(function ($state, callable $set, $get) {
                                    $fromDate = $get('detail_from_date');
                                    $toDate   = $get('detail_to_date');

                                    if ($fromDate && $toDate) {
                                        $daysDiff = now()->parse($fromDate)->diffInDays(now()->parse($toDate)) + 1;
                                        $set('detail_days_count', $daysDiff); // Set the detail_days_count automatically
                                    } else {
                                        $set('detail_days_count', 0); // Reset if no valid dates are selected
                                    }
                                }),

                            DatePicker::make('detail_to_date')
                                ->label('To Date')
                                ->dehydrated()
                                ->default(Carbon::tomorrow()->addDays(1)->format('Y-m-d'))
                                ->reactive()
                                ->required()
                                ->afterStateUpdated(function ($state, callable $set, $get) {
                                    $fromDate = $get('detail_from_date');
                                    $toDate   = $get('detail_to_date');

                                    if ($fromDate && $toDate) {
                                        $daysDiff = now()->parse($fromDate)->diffInDays(now()->parse($toDate)) + 1;
                                        $set('detail_days_count', $daysDiff); // Set the detail_days_count automatically
                                    } else {
                                        $set('detail_days_count', 0); // Reset if no valid dates are selected
                                    }
                                }),

                            TextInput::make('detail_days_count')
                                // ->disabled()
                                ->label('Number of Days')
                                // ->helperText('Type how many days this leave will be')
                                ->helperText('Type how many days this leave will be')
                                ->numeric()
                                // ->default(2)
                                ->minValue(1)
                                ->live()
                                ->required()
                                ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                    // Parse the state as a Carbon date, add one month, and set it to the end of the month
                                    $state    = (int) $state;
                                    $nextDate = Carbon::parse($get('detail_from_date'))->addDays(($state - 1))->format('Y-m-d');
                                    $set('detail_to_date', $nextDate);
                                })
                                ->maxValue(function ($get) {
                                    $balance = $get('detail_balance') ?? 0;
                                    return $balance;
                                })->validationAttribute('Leave balance'),

                        ]),
                    ]

                ),
        ];
    }

    public static function advanceRequestForm($set, $get)
    {
        $employee = Employee::find($get('employee_id'));
        $set('advanceRequest.basic_salary', $employee?->salary);
        $set('advanceRequest.detail_date', $get('application_date'));
        $set('advanceRequest.detail_deduction_starts_from', $get('application_date'));
        return [
            Fieldset::make('advanceRequest')->columnSpanFull()
                ->relationship('advanceRequest')
                ->mutateRelationshipDataBeforeCreateUsing(function ($data, $get) {

                    $data['application_type_id']   = 3;
                    $data['application_type_name'] = EmployeeApplicationV2::APPLICATION_TYPE_NAMES[EmployeeApplicationV2::APPLICATION_TYPE_ADVANCE_REQUEST];

                    $data['employee_id'] = $get('employee_id');

                    $data['advance_amount']                = $data['detail_advance_amount'];
                    $data['monthly_deduction_amount']      = $data['detail_monthly_deduction_amount'];
                    $data['deduction_ends_at']             = $data['detail_deduction_ends_at'];
                    $data['number_of_months_of_deduction'] = $data['detail_number_of_months_of_deduction'];
                    $data['deduction_starts_from']         = $data['detail_deduction_starts_from'];
                    $data['date']                          = $data['detail_date'];

                    $data['reason'] = $get('notes');
                    $date           = $get('detail_date') ?? now();
                    app(MonthClosureService::class)->ensureMonthIsOpen($date);
                    // dd($data);
                    return $data;
                })
                ->saveRelationshipsUsing(function (\Illuminate\Database\Eloquent\Model $record, array $state): void {
                    $payload = [
                        'application_id'                => $record->id,
                        'employee_id'                   => $state['employee_id'] ?? $record->employee_id,
                        'advance_amount'                => $state['detail_advance_amount'] ?? null,
                        'monthly_deduction_amount'      => $state['detail_monthly_deduction_amount'] ?? null,
                        'deduction_starts_from'         => $state['detail_deduction_starts_from'] ?? null,
                        'deduction_ends_at'             => $state['detail_deduction_ends_at'] ?? null,
                        'number_of_months_of_deduction' => $state['detail_number_of_months_of_deduction'] ?? null,
                        'date'                          => $state['detail_date'] ?? null,
                        'reason'                        => $state['reason'] ?? null,
                        'application_type_id'           => \App\Models\EmployeeApplicationV2::APPLICATION_TYPE_ADVANCE_REQUEST,
                        'application_type_name'         => \App\Models\EmployeeApplicationV2::APPLICATION_TYPE_NAMES[\App\Models\EmployeeApplicationV2::APPLICATION_TYPE_ADVANCE_REQUEST],
                    ];

                    // تحقق من إغلاق/فتح الشهر إن لزم
                    // app(\App\Services\HR\MonthClosure\MonthClosureService::class)->ensureMonthIsOpen($payload['date']);

                    $record->advanceRequest()->updateOrCreate([], $payload);
                })
                ->label('')->schema([
                    Grid::make()->columns(3)->columnSpanFull()->schema([
                        DatePicker::make('detail_date')
                            ->label('Date')
                            ->live()
                            ->maxDate(now()->toDateString())
                            ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                // Parse the state as a Carbon date, add one month, and set it to the end of the month
                                $endNextMonth = Carbon::parse($state)->addMonth()->endOfMonth()->format('Y-m-d');
                                $set('detail_deduction_starts_from', $endNextMonth);
                            })
                            ->default('Y-m-d'),
                        TextInput::make('detail_advance_amount')->numeric()->required()
                            ->label('Amount'),
                        TextInput::make('basic_salary')->numeric()->disabled()
                            ->default(0)
                            ->label('Basic salary')->helperText('Employee basic salary'),

                    ]),
                    Grid::make()->columns(3)->columnSpanFull()->schema([
                        TextInput::make('detail_monthly_deduction_amount')
                            ->numeric()
                            ->label('Monthly deduction amount')->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                $advancedAmount = $get('detail_advance_amount');
                                // dd($advancedAmount);
                                if ($state > 0 && $advancedAmount > 0) {
                                    $res = $advancedAmount / $state;

                                    $set('detail_number_of_months_of_deduction', $res);
                                    $toMonth = Carbon::now()->addMonths(($res - 2))->endOfMonth()->format('Y-m-d');
                                    $set('detail_deduction_ends_at', $toMonth);
                                }
                            }),
                        Fieldset::make()->columnSpan(1)->columns(1)->schema([
                            DatePicker::make('detail_deduction_starts_from')->minDate(now()->toDateString())
                                ->label('Deduction starts from')
                                ->default('Y-m-d')
                                ->live()
                                ->afterStateUpdated(function ($get, $set, $state) {

                                    $noOfMonths = (int) $get('detail_number_of_months_of_deduction');

                                    // $toMonth = Carbon::now()->addMonths($noOfMonths)->endOfMonth()->format('Y-m-d');

                                    $endNextMonth = Carbon::parse($state)->addMonths(($noOfMonths - 1))->endOfMonth()->format('Y-m-d');
                                    $set('detail_deduction_ends_at', $endNextMonth);
                                }),
                            DatePicker::make('detail_deduction_ends_at')->minDate(now()->toDateString())
                                ->label('Deduction ends at')
                                ->default('Y-m-d'),
                        ]),
                        TextInput::make('detail_number_of_months_of_deduction')->live(onBlur: true)
                            ->numeric()
                            ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                $advancedAmount = $get('detail_advance_amount');
                                if ($advancedAmount > 0 && $state > 0) {

                                    $res = $advancedAmount / $state;
                                    // dd($res,$state);
                                    $set('detail_monthly_deduction_amount', round($res, 2));
                                    $state = (int) $state;

                                    $toMonth = Carbon::now()->addMonths(($state - 2))->endOfMonth()->format('Y-m-d');

                                    $set('detail_deduction_ends_at', $toMonth);
                                }
                            })->minValue(1)
                            ->label('Number of months of deduction'),

                    ]),

                ]),
        ];
    }
    public static function departureRequestForm($set, $get)
    {
        $form = [
            DatePicker::make('detail_date')->maxDate(now()->toDateString())
                ->label('Date')->required()
                ->default('Y-m-d')->live(),
            TimePicker::make('detail_time')
                ->label('Time')->required(),
        ];
        return [
            Fieldset::make('missedCheckoutRequest')->label('')
                ->relationship('missedCheckoutRequest')
                ->mutateRelationshipDataBeforeCreateUsing(function ($data, $get) {

                    $data['application_type_id']   = 4;
                    $data['application_type_name'] = EmployeeApplicationV2::APPLICATION_TYPE_NAMES[EmployeeApplicationV2::APPLICATION_TYPE_DEPARTURE_FINGERPRINT_REQUEST];

                    $data['employee_id'] = $get('employee_id');

                    $data['date'] = $data['detail_date'];
                    $data['time'] = $data['detail_time'];
                    $date         = $data['detail_date'] ?? now();
                    app(MonthClosureService::class)->ensureMonthIsOpen($date);
                    return $data;
                })
                ->saveRelationshipsUsing(function (\Illuminate\Database\Eloquent\Model $record, array $state): void {
                    $payload = [
                        'application_id'        => $record->id,
                        'employee_id'           => $state['employee_id'] ?? $record->employee_id,
                        'date'                  => $state['detail_date'] ?? null,
                        'time'                  => $state['detail_time'] ?? null,
                        'application_type_id'   => \App\Models\EmployeeApplicationV2::APPLICATION_TYPE_DEPARTURE_FINGERPRINT_REQUEST,
                        'application_type_name' => \App\Models\EmployeeApplicationV2::APPLICATION_TYPE_NAMES[\App\Models\EmployeeApplicationV2::APPLICATION_TYPE_DEPARTURE_FINGERPRINT_REQUEST],
                    ];

                    $record->missedCheckoutRequest()->updateOrCreate([], $payload);
                })
                ->columns(count($form))->schema(
                    $form
                ),
        ];
    }

    public static function attendanceRequestForm()
    {

        return [
            Fieldset::make('Missed Checkin Request')
                ->label('')
                ->relationship('missedCheckinRequest')

                ->mutateRelationshipDataBeforeCreateUsing(function ($data, $get) {
                    $data['application_type_id']   = \App\Models\EmployeeApplicationV2::APPLICATION_TYPE_ATTENDANCE_FINGERPRINT_REQUEST;
                    $data['application_type_name'] = \App\Models\EmployeeApplicationV2::APPLICATION_TYPE_NAMES[\App\Models\EmployeeApplicationV2::APPLICATION_TYPE_ATTENDANCE_FINGERPRINT_REQUEST];
                    $data['employee_id']           = $get('employee_id');
                    return $data;
                })
                ->saveRelationshipsUsing(function (\Illuminate\Database\Eloquent\Model $record, array $state): void {
                    $payload = [
                        'application_id'        => $record->id,
                        'employee_id'           => $state['employee_id'] ?? $record->employee_id,
                        'date'                  => $state['date'] ?? null,
                        'time'                  => $state['time'] ?? null,
                        'application_type_id'   => \App\Models\EmployeeApplicationV2::APPLICATION_TYPE_ATTENDANCE_FINGERPRINT_REQUEST,
                        'application_type_name' => \App\Models\EmployeeApplicationV2::APPLICATION_TYPE_NAMES[\App\Models\EmployeeApplicationV2::APPLICATION_TYPE_ATTENDANCE_FINGERPRINT_REQUEST],
                    ];

                    $record->missedCheckinRequest()->updateOrCreate([], $payload);
                })
                ->mutateRelationshipDataBeforeCreateUsing(function ($data, $get) {

                    $data['application_type_id']   = 2;
                    $data['application_type_name'] = EmployeeApplicationV2::APPLICATION_TYPE_NAMES[EmployeeApplicationV2::APPLICATION_TYPE_ATTENDANCE_FINGERPRINT_REQUEST];

                    $data['employee_id'] = $get('employee_id');

                    // $data['date'] = $data['detail_date'];
                    // $data['time'] = $data['detail_time'];
                    // $date         = $data['detail_date'] ?? now();
                    // app(MonthClosureService::class)->ensureMonthIsOpen($date);
                    return $data;
                })

                ->columns(2)
                ->schema(
                    [
                        DatePicker::make('date')->maxDate(now()->toDateString())
                            ->label('Date')->required()
                            ->default('Y-m-d')
                            ->maxDate(now()->toDateString())
                        // ->minDate(fn($get): string => (Carbon::parse($get('../application_date'))->startOfMonth()->toDateString()))
                        ,
                        TimePicker::make('time')
                            ->default(now())
                            ->seconds(false)
                            ->label('Time')->required(),
                    ]
                ),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = static::getModel()::query();

        if (isStuff()) {
            $query->where('employee_id', auth()->user()->employee->id);
        }

        $query->whereHas('employee', function ($q) {
            $q->whereNull('deleted_at'); // ignore soft-deleted employees
        });
        return $query->forBranchManager();
    }
}
