<?php

namespace App\Filament\Clusters\HRApplicationsCluster\Resources\LeaveBalanceResource\Pages;

use Filament\Actions\CreateAction;
use Exception;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use App\Filament\Clusters\HRApplicationsCluster\Resources\LeaveBalanceResource;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Validation\Rules\Unique;

class ListLeaveBalances extends ListRecords
{
    protected static string $resource = LeaveBalanceResource::class;


    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Bulk create'),
            Action::make('Individual create')
                ->visible(function () {
                    if (isSuperAdmin() || isBranchManager() || isSystemManager()) {
                        return true;
                    }
                    return false;
                })
                ->action(function ($data) {
                    // Handle the creation of the leave balance record
                    try {
                        $employeeBranchId = Employee::find($data['employee_id'])?->branch_id;
                        $data['branch_id'] = $employeeBranchId;
                        $data['created_by'] = auth()->id();
                        LeaveBalance::create($data); // Save the data to the database

                        Notification::make()->body('Leave balance created successfully.')->send(); // Success message
                    } catch (Exception $e) {
                        Notification::make()->body('Error creating leave balance: ' . $e->getMessage())->send(); // Error message
                    }
                })
                ->schema(function () {
                    return [
                        Fieldset::make()->columnSpanFull()->columns(5)->schema([
                            Select::make('employee_id')
                                ->label('Employee')
                                ->required()
                                ->searchable()
                                ->unique(
                                    ignoreRecord: true,
                                    modifyRuleUsing: function (Unique $rule,  Get $get, $state) {
                                        return $rule->where('employee_id', $state)
                                            ->where('leave_type_id', $get('leave_type_id'))
                                            ->where('year', $get('year'))
                                            ->where('month', $get('../../month'))
                                        ;
                                    }
                                )->validationMessages([
                                    'unique' => 'Balance already created'
                                ])
                                ->getSearchResultsUsing(fn(string $search): array => Employee::where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id')->toArray())
                                ->getOptionLabelUsing(fn($value): ?string => Employee::find($value)?->name),
                            Select::make('leave_type_id')->label('Leave type')
                                ->required()

                                ->live()
                                ->options(LeaveType::where('active', 1)->select('name', 'id')->get()->pluck('name', 'id'))
                                ->afterStateUpdated(function (Get $get, Set $set, $state, $livewire) {
                                    $leaveType = LeaveType::find($state);
                                    $set('balance', $leaveType->count_days);
                                }),
                            Select::make('year')->options([
                                2024 => 2024,
                                2025 => 2025,
                                2026 => 2026,
                                2027 => 2027,
                            ])->required(),
                            Select::make('month')->options(getMonthArrayWithKeys()),
                            TextInput::make('balance')->label('Balance')
                                ->numeric()
                                ->live()
                                ->required(),
                        ])
                    ];
                }),
        ];
    }
}
