<?php

namespace App\Filament\Resources\ApprovalPolicies\Schemas;

use App\Models\AdvanceWage;
use App\Models\Branch;
use App\Models\EmployeeApplicationV2;
use App\Models\EmployeeOvertime;
use App\Models\User;
use App\Modules\HR\ApprovalPolicies\Enums\ApprovalMode;
use App\Modules\HR\ApprovalPolicies\Enums\ApprovalPolicyStepType;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Spatie\Permission\Models\Role;

class ApprovalPolicyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('mode')
                    ->default(ApprovalMode::CONFIGURED_STEPS)
                    ->dehydrated(),

                Wizard::make([
                    Step::make(__('Basic Information'))
                        ->icon('heroicon-o-information-circle')
                        ->columnSpanFull()
                        ->schema([
                            Fieldset::make()->columnSpanFull()->schema([
                                Grid::make(3)
                                    ->columnSpanFull()
                                    ->schema([
                                        TextInput::make('name')
                                            ->label(__('Name'))
                                            ->maxLength(255)
                                            ->placeholder(__('Example: Leave approval - branch manager')),


                                        Select::make('branch_id')
                                            ->label(__('Branch'))
                                            ->placeholder('All')
                                            ->options(fn(): array => Branch::query()
                                                ->orderBy('name')
                                                ->pluck('name', 'id')
                                                ->all())
                                            ->searchable()
                                            ->nullable()
                                            ->helperText(__('Select all to apply this policy to every branch.')),
                                        Toggle::make('active')
                                            ->label(__('Active'))
                                            ->default(true)
                                            ->inline(false),

                                    ]),

                                Grid::make(2)
                                    ->columnSpanFull()
                                    ->schema([
                                        Select::make('approvable_type')
                                            ->label(__('Approval Subject'))
                                            ->options(self::approvableTypeOptions())
                                            ->required()
                                            ->searchable()
                                            ->live()
                                            ->afterStateUpdated(function (Set $set): void {
                                                $set('application_type_id', null);
                                            }),

                                        Select::make('application_type_id')
                                            ->label(__('Employee Request Type'))
                                            ->options(EmployeeApplicationV2::APPLICATION_TYPE_NAMES)
                                            ->searchable()
                                            ->nullable()
                                            ->helperText(__('Leave empty to apply to all employee request types.'))
                                            ->visible(fn(Get $get): bool => $get('approvable_type') === EmployeeApplicationV2::class),

                                    ]),
                            ])
                        ]),

                    Step::make(__('Workflow Steps'))
                        ->icon('heroicon-o-queue-list')
                        ->schema([
                            Repeater::make('policySteps')
                                ->label(__('Route Steps'))
                                ->relationship('policySteps')
                                ->columnSpanFull()
                                // ->table([
                                //     TableColumn::make(__('Approver Type'))->width('18rem'),
                                //     TableColumn::make(__('Approver User'))->alignCenter()->width('24rem'),
                                //     TableColumn::make(__('Approval Role'))->alignCenter()->width('18rem'),
                                //     TableColumn::make(__('Manager Level'))->alignCenter()->width('18rem'),


                                // ])
                                ->columns(3)
                                ->schema([

                                    Select::make('approver_type')
                                        ->label(__('Approver Type'))
                                        ->options(self::stepTypeOptions())
                                        ->searchable()
                                        ->optionsLimit(8)
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(function (?string $state, Set $set): void {
                                            if ($state !== ApprovalPolicyStepType::CUSTOM_USER) {
                                                $set('approver_user_id', null);
                                            }

                                            if ($state !== ApprovalPolicyStepType::CUSTOM_ROLE) {
                                                $set('approver_role_id', null);
                                            }

                                            if ($state !== ApprovalPolicyStepType::MANAGER_LEVEL) {
                                                $set('manager_level', null);
                                            }
                                        }),

                                    Select::make('approver_user_id')
                                        ->label(__('Approver'))
                                        ->options(fn(): array => User::query()
                                            ->orderBy('name')
                                            ->pluck('name', 'id')
                                            ->all())
                                        ->searchable()
                                        ->optionsLimit(8)
                                        ->required(fn(Get $get): bool => $get('approver_type') === ApprovalPolicyStepType::CUSTOM_USER)
                                        ->visible(fn(Get $get): bool => $get('approver_type') === ApprovalPolicyStepType::CUSTOM_USER),

                                    Select::make('approver_role_id')
                                        ->label(__('Role'))
                                        ->options(fn(): array => Role::query()
                                            ->orderBy('name')
                                            ->pluck('name', 'id')
                                            ->all())
                                        ->searchable()
                                        ->optionsLimit(8)
                                        ->required(fn(Get $get): bool => $get('approver_type') === ApprovalPolicyStepType::CUSTOM_ROLE)
                                        ->visible(fn(Get $get): bool => $get('approver_type') === ApprovalPolicyStepType::CUSTOM_ROLE),

                                    TextInput::make('manager_level')
                                        ->label(__('Manager Level'))
                                        ->numeric()
                                        ->minValue(1)
                                        ->step(1)
                                        ->required(fn(Get $get): bool => $get('approver_type') === ApprovalPolicyStepType::MANAGER_LEVEL)
                                        ->visible(fn(Get $get): bool => $get('approver_type') === ApprovalPolicyStepType::MANAGER_LEVEL),

                                ])
                                ->required()
                                ->minItems(1)
                                ->defaultItems(1)
                                ->orderColumn('step_order')
                                ->reorderable()
                                ->reorderableWithDragAndDrop()
                                ->reorderableWithButtons()
                                ->collapsible()
                                ->cloneable()
                                ->columnSpanFull(),
                        ]),
                ])
                    ->skippable()
                    ->columnSpanFull(),

            ])
            ->columns(1);
    }

    private static function approvableTypeOptions(): array
    {
        return [
            EmployeeApplicationV2::class => __('Employee Requests'),
            EmployeeOvertime::class => __('Employee Overtime'),
            AdvanceWage::class => __('Advance Wages'),
        ];
    }

    private static function stepTypeOptions(): array
    {
        return [
            ApprovalPolicyStepType::DIRECT_MANAGER => __('Direct Manager'),
            ApprovalPolicyStepType::BRANCH_MANAGER => __('Branch Manager'),
            ApprovalPolicyStepType::MANAGER_LEVEL => __('Manager Chain Level'),
            ApprovalPolicyStepType::CUSTOM_USER => __('Custom User'),
            ApprovalPolicyStepType::CUSTOM_ROLE => __('Custom Role'),
        ];
    }
}
