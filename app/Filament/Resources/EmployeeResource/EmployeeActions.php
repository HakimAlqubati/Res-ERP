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
            ->fillForm(fn(Employee $record): array => $record->load('branchLogs')->toArray())
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
                                    ])
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

                Notification::make()
                    ->title(__('lang.success'))
                    ->body(__('lang.branch_changed_successfully') ?? 'Branch changed successfully')
                    ->success()
                    ->send();
            });
    }
}