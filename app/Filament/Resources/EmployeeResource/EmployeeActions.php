<?php 
namespace App\Filament\Resources\EmployeeResource;

use App\Models\Branch;
use App\Models\Employee;
use App\Services\HR\EmployeeBranchTransferService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Notifications\Notification;

class EmployeeActions
{
    public static function changeBranch()
    {
        return Action::make('changeBranch')->icon('heroicon-o-arrow-path-rounded-square')
            ->label(__('lang.change_branch')) // Label for the action button
            ->visible(isSystemManager() || isSuperAdmin())
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
}