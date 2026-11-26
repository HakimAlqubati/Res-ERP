<?php 
namespace App\Filament\Resources\EmployeeResource;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeSalaryLog;
use App\Modules\HR\Payroll\Contracts\EmployeeSalaryResolverInterface;
use App\Modules\HR\SalaryIncrease\Contracts\EmployeeSalaryLogServiceInterface;
use App\Services\HR\EmployeeBranchTransferService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;
use Throwable;

class EmployeeActions
{
    public static function changeBranch()
    {
        return Action::make('changeBranch')->icon('heroicon-o-arrow-path-rounded-square')
            ->label(__('lang.change_branch')) // Label for the action button
            ->visible(isSystemManager() || isSuperAdmin() || isHR())
            // ->icon('heroicon-o-annotation') // Icon for the button
            ->modalHeading(__('lang.change_employee_branch')) // Modal heading
            ->modalButton('Save')                    // Button inside the modal
            ->fillForm(function(Employee $record): array {
                $data = $record->load('branchLogs')->toArray();
                unset($data['branch_id']); // Remove current branch so the select is empty
                return $data;
            })
            ->schema([
                Tabs::make('Tabs')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make(__('lang.change_branch'))
                            ->icon('heroicon-o-arrow-path')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        Select::make('branch_id')
                                            ->label(__('lang.select_new_branch'))
                                            ->searchable()
                                            ->options(Branch::query()
                                                ->where('active', true)
                                                ->pluck('name', 'id'))
                                            ->required()
                                            ->preload()
                                            ->live()
                                            ->rules([
                                                fn(Get $get, Employee $record) => new \App\Rules\HR\Employee\BranchChangeRule(
                                                    $record->branch_id,
                                                    $record->id,
                                                    $get('start_at'),
                                                    $get('end_at')
                                                )
                                            ]),
                                        DatePicker::make('start_at')
                                            ->label(__('lang.start_date'))
                                            ->default(now())
                                            ->live()
                                            ->required(),
                                        DatePicker::make('end_at')
                                            ->label(__('lang.end_date')),
                                    ]),
                                \Filament\Schemas\Components\Section::make(__('lang.assign_shifts') ?? 'Assign Shifts')
                                    ->description(__('lang.assign_shifts_description') ?? 'You can assign new shifts for this branch. If left empty, the employee will have no shift.')
                                    ->schema([
                                        \Filament\Forms\Components\ToggleButtons::make('periods')
                                            ->label('Work Periods')
                                            ->columns(3)->multiple()
                                            ->options(function (Get $get) {
                                                $branchId = $get('branch_id');
                                                if (!$branchId) return [];
                                                return \App\Models\WorkPeriod::where('branch_id', $branchId)->pluck('name', 'id');
                                            })
                                            ->required(fn(Get $get) => !empty($get('branch_id')))
                                            ->helperText('Select the employee\'s work periods.'),
                                        \Filament\Schemas\Components\Fieldset::make()->schema([
                                            \Filament\Forms\Components\CheckboxList::make('period_days')
                                                ->label('Days of Work')
                                                ->columns(3)
                                                ->options(\App\Enums\DayOfWeek::options())
                                                ->required(fn(Get $get) => !empty($get('periods')))
                                                ->bulkToggleable()
                                                ->helperText('Select the days this period applies to.'),
                                        ]),
                                    ])
                                    ->visible(fn(Get $get) => filled($get('branch_id'))),
                            ]),
                        Tab::make(__('lang.branch_logs_count'))
                            ->icon('heroicon-o-list-bullet')
                            ->schema([
                                Repeater::make('branchLogs')
                                    ->relationship()
                                    ->table([
                                        TableColumn::make(__('Branch'))->width('33%'),
                                        TableColumn::make(__('Start Date'))->width('33%'),
                                        TableColumn::make(__('End Date'))->width('33%'),
                                        // TableColumn::make(__('Created By'))->width('30%'),
                                    ])
                                    ->schema([
                                        Select::make('branch_id')
                                            ->label(__('lang.branch'))
                                            ->options(Branch::all()->pluck('name', 'id'))
                                            ->disabled()
                                            ->columnSpan(1),
                                        DatePicker::make('start_at')
                                            ->label(__('lang.start_date'))
                                            ->disabled()
                                            ->columnSpan(1),
                                        DatePicker::make('end_at')
                                            ->label(__('lang.end_date'))
                                            ->disabled()
                                            ->columnSpan(1),
                                        TextInput::make('created_by')->hidden()
                                            ->label(__('lang.created_by'))
                                            ->formatStateUsing(fn($state, $record) => $record?->createdBy?->name ?? '-')
                                            ->disabled()
                                            ->columnSpan(1),
                                    ])
                                    ->columns(4)
                                    ->addable(false)
                                    ->deletable(false)
                                    ->reorderable(false)
                                    ->columnSpanFull()
                            ]),
                    ])
            ])
            ->action(function (array $data, Employee $record) {
                app(EmployeeBranchTransferService::class)->execute(
                    employee: $record,
                    newBranchId: (int) $data['branch_id'],
                    startAt: $data['start_at'],
                    endAt: $data['end_at'] ?? null,
                );

                if (!empty($data['periods']) && !empty($data['period_days'])) {
                    try {
                        $shiftData = [
                            'start_date' => $data['start_at'],
                            'end_date' => $data['end_at'] ?? null,
                            'periods' => $data['periods'],
                            'period_days' => $data['period_days'],
                        ];
                        $service = new \App\Modules\HR\EmployeeWorkPeriods\EmployeeWorkPeriodService();
                        $service->assignPeriodsToEmployee($record, $shiftData);
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::alert('Error adding new periods during branch transfer: ' . $e->getMessage());
                        Notification::make()
                            ->title(__('lang.warning') ?? 'Warning')
                            ->body('Branch changed, but failed to assign shifts: ' . $e->getMessage())
                            ->warning()
                            ->send();
                        return;
                    }
                }

                Notification::make()
                    ->title(__('lang.success'))
                    ->body(__('lang.branch_changed_successfully') ?? 'Branch changed successfully')
                    ->success()
                    ->send();
            });
    }

    public static function changeSalary(): Action
    {
        return Action::make('changeSalary')
            ->icon('heroicon-o-banknotes')
            ->color('warning')
            ->label(__('Change Salary'))
            ->visible(fn (): bool => isSuperAdmin() || isSystemManager() || isFinanceManager())
            ->modalHeading(__('Change Employee Salary'))
            ->modalSubmitActionLabel(__('Save'))
            ->fillForm(function (Employee $record): array {
                $now = now();
                $effectiveSalary = app(EmployeeSalaryResolverInterface::class)
                    ->resolveForPeriod($record, $now->year, $now->month);

                return [
                    'current_salary' => round($effectiveSalary, 2),
                    'new_salary'     => null,
                    'effective_date' => $now->toDateString(),
                    'notes'          => null,
                    'salary_history' => $record->salaryLogs()
                        ->orderByDesc('effective_from')
                        ->orderByDesc('id')
                        ->get()
                        ->map(fn (EmployeeSalaryLog $log): array => [
                            'effective_from' => $log->effective_from?->format('Y-m-d') ?? '-',
                            'old_salary'     => $log->old_salary !== null ? formatMoneyWithCurrency($log->old_salary) : '-',
                            'new_salary'     => formatMoneyWithCurrency($log->new_salary),
                            'source'         => EmployeeSalaryLog::getSourceLabel($log->source),
                            'notes'          => $log->notes ?? '-',
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->schema([
                Tabs::make('SalaryTabs')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make(__('Change Salary'))
                            ->icon('heroicon-o-banknotes')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        TextInput::make('current_salary')
                                            ->label(__('Current Effective Salary'))
                                            ->numeric()
                                            ->disabled()
                                            ->dehydrated(false),

                                        TextInput::make('new_salary')
                                            ->label(__('New Salary'))
                                            ->numeric()
                                            ->inputMode('decimal')
                                            ->minValue(0)
                                            ->required(),

                                        DatePicker::make('effective_date')
                                            ->label(__('Effective Date'))
                                            ->default(now()->toDateString())
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(function ($state, Set $set, Employee $record): void {
                                                try {
                                                    $date = $state ? Carbon::parse($state) : now();
                                                } catch (Throwable) {
                                                    return;
                                                }

                                                $effectiveSalary = app(EmployeeSalaryResolverInterface::class)
                                                    ->resolveForPeriod($record, $date->year, $date->month);
                                                $set('current_salary', round($effectiveSalary, 2));
                                            }),
                                    ]),

                                Textarea::make('notes')
                                    ->label(__('lang.notes'))
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ]),

                        Tab::make(__('Salary History'))
                            ->icon('heroicon-o-clock')
                            ->schema([
                                Repeater::make('salary_history')
                                    ->label('')
                                    ->table([
                                        TableColumn::make(__('Effective Month'))->width('20%'),
                                        TableColumn::make(__('Old Salary'))->width('20%'),
                                        TableColumn::make(__('New Salary'))->width('20%'),
                                        TableColumn::make(__('Source'))->width('20%'),
                                        TableColumn::make(__('lang.notes'))->width('20%'),
                                    ])
                                    ->schema([
                                        TextInput::make('effective_from')->disabled(),
                                        TextInput::make('old_salary')->disabled(),
                                        TextInput::make('new_salary')->disabled(),
                                        TextInput::make('source')->disabled(),
                                        TextInput::make('notes')->disabled(),
                                    ])
                                    ->addable(false)
                                    ->deletable(false)
                                    ->reorderable(false)
                                    ->dehydrated(false)
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ])
            ->action(function (array $data, Employee $record, Action $action): void {
                try {
                    app(EmployeeSalaryLogServiceInterface::class)->recordSalaryChange(
                        employee: $record,
                        newSalary: (float) $data['new_salary'],
                        effectiveDate: (string) $data['effective_date'],
                        source: EmployeeSalaryLog::SOURCE_DIRECT_EDIT,
                        notes: $data['notes'] ?? null,
                        actorId: auth()->id(),
                    );

                    Notification::make()
                        ->title(__('lang.success'))
                        ->body(__('Employee salary updated successfully.'))
                        ->success()
                        ->send();
                } catch (ValidationException $e) {
                    Notification::make()
                        ->title(__('Validation Error'))
                        ->body(collect($e->errors())->flatten()->implode("\n"))
                        ->danger()
                        ->send();

                    $action->halt();
                } catch (Throwable $e) {
                    Notification::make()
                        ->title(__('lang.error'))
                        ->body($e->getMessage())
                        ->danger()
                        ->send();

                    $action->halt();
                }
            });
    }

    public static function active(): Action
    {
        return Action::make('active')
            ->label(__('lang.enable_account'))
            ->color('success')
            ->icon('heroicon-o-check-circle')
            ->requiresConfirmation()
            ->visible(fn(Employee $record) => !$record->active)
            ->action(function (Employee $record) {
                \Illuminate\Support\Facades\DB::beginTransaction();
                try {
                    $record->update(['active' => true]);
                    \Illuminate\Support\Facades\DB::commit();
                    
                    Notification::make()
                        ->title(__('lang.success'))
                        ->success()
                        ->send();
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\DB::rollBack();
                    
                    Notification::make()
                        ->title(__('lang.error'))
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    public static function inactive(): Action
    {
        return Action::make('inactive')
            ->label(__('lang.disable_account'))
            ->color('danger')
            ->icon('heroicon-o-x-circle')
            ->requiresConfirmation()
            ->visible(fn(Employee $record) => $record->active)
            ->action(function (Employee $record) {
                \Illuminate\Support\Facades\DB::beginTransaction();
                try {
                    $record->update(['active' => false]);
                    \Illuminate\Support\Facades\DB::commit();
                    
                    Notification::make()
                        ->title(__('lang.success'))
                        ->success()
                        ->send();
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\DB::rollBack();
                    
                    Notification::make()
                        ->title(__('lang.error'))
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    public static function attendance(): Action{
        return  \Filament\Actions\Action::make('attendance_report')
        ->label(__('lang.attendance_report'))
        ->color('info')
        ->icon('heroicon-o-chart-bar')
        ->url(fn($record) => \App\Filament\Clusters\HRAttendanceReport\Resources\EmployeeAttednaceReportResource::getUrl('index', [
          'tableFilters[employee_id]' => $record->id,
          'tableFilters[date_range][start_date]' => now()->startOfMonth()->toDateString(),
          'tableFilters[date_range][end_date]' => now()->endOfMonth()->toDateString(),
        ]))
        ->openUrlInNewTab();
    }

    /**
     * Reusable Rehire Action shared across Employee table, Edit page, and View page.
     */
    public static function rehire(?\Closure $visibleUsing = null): Action
    {
        return Action::make('rehire')
            ->label(__('lang.rehire'))
            ->icon('heroicon-o-arrow-path')
            ->color('success')
            ->visible($visibleUsing ?? fn (Employee $record): bool => ! $record->active)
            ->schema(fn (Employee $record): array => [
                DatePicker::make('join_date')
                    ->label(__('lang.join_date'))
                    ->default(now())
                    ->required(),
                TextInput::make('salary')
                    ->label(__('lang.salary'))
                    ->numeric()
                    ->inputMode('decimal')
                    ->minValue(0)
                    ->default($record->salary)
                    ->nullable(),
                Textarea::make('notes')
                    ->label(__('lang.notes')),
            ])
            ->action(function (Employee $record, array $data, Action $action): void {
                try {
                    app(\App\Modules\HR\Employee\Services\EmployeeLifecycleService::class)->rehire($record, $data);

                    Notification::make()
                        ->title(__('lang.employee_rehired_successfully'))
                        ->success()
                        ->send();
                } catch (Throwable $e) {
                    Notification::make()
                        ->title(__('lang.error_occurred'))
                        ->body($e->getMessage())
                        ->danger()
                        ->send();

                    $action->halt();
                }
            });
    }
}