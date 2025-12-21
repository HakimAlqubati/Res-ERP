<?php

namespace App\Filament\Clusters\HRAttendanceReport\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Actions\Action;
use App\Filament\Clusters\HRAttendanceReport;
use App\Filament\Clusters\HRTaskReport;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeApplication;
use App\Models\EmployeeApplicationV2;
use App\Models\LeaveType;
use App\Models\Task;
use App\Models\TaskLog;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Support\Colors\Color;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextColumn\TextColumnSize;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class EmployeeLeaveReportResource extends Resource
{
    protected static ?string $model = Task::class;
    protected static ?string $slug = 'employee-leave-report';
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = HRTaskReport::class;

    public static function getModelLabel(): string
    {
        return __('lang.leave_report');
    }

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 6;

    public static function table(Table $table): Table
    {

        return $table
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(50)
            ->emptyStateHeading(__('lang.no_data'))->striped()
            ->columns([
                TextColumn::make('employee_id')->label(__('lang.id'))->searchable(isGlobal: true)->alignCenter(true)->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('employee_no')->label(__('lang.employee_number'))->searchable(isGlobal: true)->alignCenter(true),

                TextColumn::make('employee_name')->label(__('lang.name'))->wrap(true)->limit(15),
                TextColumn::make('request_id')->label('Advance id')->alignCenter(true)->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('CountDays')->label('Count days')->alignCenter(true)
                    ->getStateUsing(function ($record) {
                        $employee = Employee::find($record->employee_id);

                        // dd($employee->approved_leave_requests->where('id',42)->first()['days_count']);
                        $leave =  $employee->approved_leave_requests->where('id', $record->request_id)->first() ?? null;
                        if ($leave) {
                            return $leave['days_count'];
                        }
                    }),
                TextColumn::make('LeaveType')->label(__('lang.leave_type'))->alignCenter(true)
                    ->getStateUsing(function ($record) {
                        $employee = Employee::find($record->employee_id);

                        // dd($employee->approved_leave_requests->where('id',42)->first()['days_count']);
                        $leave =  $employee->approved_leave_requests->where('id', $record->request_id)->first() ?? null;

                        if ($leave) {
                            return LeaveType::find($leave['leave_type_id'])?->name ?? '';
                        }
                    }),
                TextColumn::make('FromDate')->label(__('lang.from_date'))->alignCenter(true)
                    ->getStateUsing(function ($record) {
                        $employee = Employee::find($record->employee_id);

                        // dd($employee->approved_leave_requests->where('id',42)->first()['days_count']);
                        $leave =  $employee->approved_leave_requests->where('id', $record->request_id)->first() ?? null;

                        if ($leave) {
                            return $leave['from_date'] ?? '';
                        }
                    }),
                TextColumn::make('ToDate')->label(__('lang.to_date'))->alignCenter(true)
                    ->getStateUsing(function ($record) {
                        $employee = Employee::find($record->employee_id);

                        // dd($employee->approved_leave_requests->where('id',42)->first()['days_count']);
                        $leave =  $employee->approved_leave_requests->where('id', $record->request_id)->first() ?? null;

                        if ($leave) {
                            return $leave['to_date'] ?? '';
                        }
                    }),



            ])
            ->filters([
                SelectFilter::make('hr_employees.branch_id')->placeholder(__('lang.branch'))
                    ->label(__('lang.branch'))
                    ->options(Branch::where('active', 1)
                        ->select('name', 'id')->get()->pluck('name', 'id'))->searchable(),
                SelectFilter::make('hr_employees.id')
                    ->placeholder(__('lang.employee'))
                    ->label(__('lang.employee'))
                    ->getSearchResultsUsing(fn(string $search): array => Employee::where('name', 'like', "%{$search}%")->limit(5)->pluck('name', 'id')->toArray())
                    ->getOptionLabelUsing(fn($value): ?string => Employee::find($value)?->name)
                    ->searchable(),

            ], FiltersLayout::AboveContent)
            ->recordActions([
                Action::make('details')->hidden()

                    ->schema(function ($record) {
                        // Retrieve installments for the given advance_id
                        $installments = EmployeeApplicationV2::find($record->advance_id)->advanceInstallments;


                        // Define the Repeater component
                        return [
                            Repeater::make('installments')
                                ->schema([
                                    TextInput::make('installment_amount')
                                        ->label('Installment Amount')
                                        ->disabled(),

                                    DatePicker::make('due_date')
                                        ->label('Due Date')
                                        ->disabled(),

                                    Checkbox::make('is_paid')
                                        ->label('Is Paid')
                                        ->disabled(),

                                    DatePicker::make('paid_date')
                                        ->label('Paid Date')
                                        ->disabled(),
                                ])
                                ->defaultItems(count($installments)) // Set default rows based on installment count
                                ->createItemButtonLabel(false) // Hide the "Add Item" button
                                ->disableItemCreation() // Prevent adding rows manually
                                ->columns(4) // Number of columns per row
                                ->default(array_map(function ($installment) {
                                    // dd($installment['installment_amount']);
                                    return [
                                        'installment_amount' => $installment['installment_amount'],
                                        'due_date' => $installment['due_date'],
                                        'is_paid' => (bool) $installment['is_paid'],
                                        'paid_date' => $installment['paid_date'],
                                    ];
                                }, $installments->toArray())),
                        ];
                    })
                    ->disabledForm()->modalSubmitAction(false)->modalCancelAction(false)
            ])
        ;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmployeeLeaveReport::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = Employee::select(
            'hr_employees.branch_id as branch_id',
            'hr_employees.id as employee_id',
            'hr_employees.employee_no as employee_no',
            'hr_employees.name as employee_name',
            'hr_employee_applications.id as request_id',
            // DB::raw('SUM(TIME_TO_SEC(hr_task_logs.total_hours_taken)) as total_spent_seconds')

        )->join('hr_employee_applications',  'hr_employees.id', '=', 'hr_employee_applications.employee_id')
            ->where('hr_employee_applications.application_type_id', 1)
            ->where('hr_employee_applications.status', EmployeeApplicationV2::STATUS_APPROVED)

            // ->where('hr_task_logs.log_type', TaskLog::TYPE_MOVED)
            // ->whereJsonContains('hr_task_logs.details->to', Task::STATUS_CLOSED, '!=')
        ;

        $query = $query->groupBy('hr_employees.id', 'hr_employees.branch_id', 'hr_employees.employee_no', 'hr_employees.name', 'hr_employee_applications.id');

        // dd($query->toSql());
        return $query;
        // return $query->orderBy('hr_tasks.id','desc');


    }

    public static function canViewAny(): bool
    {
        if (isSuperAdmin() || isSystemManager() || isFinanceManager()) {
            return true;
        }
        return false;
    }
}
