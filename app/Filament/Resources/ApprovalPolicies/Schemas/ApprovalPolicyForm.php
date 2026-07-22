<?php

namespace App\Filament\Resources\ApprovalPolicies\Schemas;

use App\Models\AdvanceWage;
use App\Models\Branch;
use App\Models\EmployeeApplicationV2;
use App\Models\EmployeeOvertime;
use App\Models\User;
use App\Modules\HR\ApprovalPolicies\Enums\ApprovalMode;
use App\Modules\HR\ApprovalPolicies\Enums\ApprovalPolicyStepType;
use App\Modules\HR\ApprovalPolicies\Rules\PolicyNotInUse;
use App\Modules\HR\ApprovalPolicies\Rules\UniqueApprovalPolicy;
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
                                            ->placeholder(__('Example: Leave approval - branch manager'))
                                            ->rules([
                                                fn (?\Illuminate\Database\Eloquent\Model $record) => function (string $attribute, $value, \Closure $fail) use ($record) {
                                                    if ($record) {
                                                        (new PolicyNotInUse($record))->validate($attribute, $value, $fail);
                                                    }
                                                },
                                                fn (Get $get, ?\Illuminate\Database\Eloquent\Model $record) => new UniqueApprovalPolicy(
                                                    ignoreId: $record?->id,
                                                    approvableType: $get('approvable_type'),
                                                    applicationTypeId: $get('application_type_id'),
                                                    branchIds: $get('branch_ids')
                                                ),
                                            ]),


                                        Select::make('branch_ids')
                                            ->label(__('Branches'))
                                            ->placeholder('All')
                                            ->options(fn(): array => Branch::query()
                                            ->active()
                                                ->orderBy('name')
                                                ->pluck('name', 'id')
                                                ->all())
                                            ->multiple()
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
                                        Hidden::make('approvable_type'),
                                        Hidden::make('application_type_id'),

                                        Select::make('approval_subject_combined')
                                            ->label(__('Approval Subject'))
                                            ->options(function (): array {
                                                $options = [
                                                    EmployeeOvertime::class => __('Employee Overtime'),
                                                    AdvanceWage::class => __('Advance Wages'),
                                                    EmployeeApplicationV2::class . ':all' => __('All Employee Requests'),
                                                ];

                                                foreach (EmployeeApplicationV2::APPLICATION_TYPE_NAMES as $id => $name) {
                                                    $options[EmployeeApplicationV2::class . ':' . $id] = __('Employee Requests') . ' - ' . $name;
                                                }

                                                return $options;
                                            })
                                            ->required()
                                            ->searchable()
                                            ->live()
                                            ->afterStateHydrated(function (Select $component, Get $get): void {
                                                $type = $get('approvable_type');
                                                $appId = $get('application_type_id');

                                                if ($type === EmployeeApplicationV2::class) {
                                                    $component->state($type . ':' . ($appId ?? 'all'));
                                                } else {
                                                    $component->state($type);
                                                }
                                            })
                                            ->afterStateUpdated(function (?string $state, Set $set): void {
                                                if ($state && str_contains($state, ':')) {
                                                    [$type, $appId] = explode(':', $state);
                                                    $set('approvable_type', $type);
                                                    $set('application_type_id', $appId === 'all' ? null : $appId);
                                                } else {
                                                    $set('approvable_type', $state);
                                                    $set('application_type_id', null);
                                                }
                                            })
                                            ->dehydrated(false)
                                            ->columnSpanFull(),

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
