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
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
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

                Section::make(__('Approval Policy'))
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->label(__('Name'))
                                ->maxLength(255)
                                ->placeholder(__('Example: Leave approval - branch manager')),

                            Toggle::make('active')
                                ->label(__('Active'))
                                ->default(true)
                                ->inline(false),
                        ]),

                        Grid::make(3)->schema([
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
                                ->label(__('Employee Application Type'))
                                ->options(EmployeeApplicationV2::APPLICATION_TYPE_NAMES)
                                ->searchable()
                                ->nullable()
                                ->helperText(__('Leave empty to apply to all employee application types.'))
                                ->visible(fn (Get $get): bool => $get('approvable_type') === EmployeeApplicationV2::class),

                            Select::make('branch_id')
                                ->label(__('Branch'))
                                ->options(fn (): array => Branch::query()
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all())
                                ->searchable()
                                ->nullable()
                                ->helperText(__('Leave empty to use this policy as a global fallback.')),
                        ]),
                    ])
                    ->columns(1),

                Section::make(__('Approval Route Template'))
                    ->schema([
                        Repeater::make('policySteps')
                            ->label(__('Route Steps'))
                            ->relationship('policySteps')
                            ->schema([
                                Grid::make(3)->schema([
                                    Select::make('approver_type')
                                        ->label(__('Approver Type'))
                                        ->options(self::stepTypeOptions())
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
                                        ->options(fn (): array => User::query()
                                            ->orderBy('name')
                                            ->pluck('name', 'id')
                                            ->all())
                                        ->searchable()
                                        ->preload()
                                        ->required(fn (Get $get): bool => $get('approver_type') === ApprovalPolicyStepType::CUSTOM_USER)
                                        ->visible(fn (Get $get): bool => $get('approver_type') === ApprovalPolicyStepType::CUSTOM_USER),

                                    Select::make('approver_role_id')
                                        ->label(__('Role'))
                                        ->options(fn (): array => Role::query()
                                            ->orderBy('name')
                                            ->pluck('name', 'id')
                                            ->all())
                                        ->searchable()
                                        ->preload()
                                        ->required(fn (Get $get): bool => $get('approver_type') === ApprovalPolicyStepType::CUSTOM_ROLE)
                                        ->visible(fn (Get $get): bool => $get('approver_type') === ApprovalPolicyStepType::CUSTOM_ROLE),

                                    TextInput::make('manager_level')
                                        ->label(__('Manager Level'))
                                        ->numeric()
                                        ->minValue(1)
                                        ->step(1)
                                        ->required(fn (Get $get): bool => $get('approver_type') === ApprovalPolicyStepType::MANAGER_LEVEL)
                                        ->visible(fn (Get $get): bool => $get('approver_type') === ApprovalPolicyStepType::MANAGER_LEVEL),
                                ]),
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
            ->columns(1);
    }

    private static function approvableTypeOptions(): array
    {
        return [
            EmployeeApplicationV2::class => __('Employee Applications'),
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
