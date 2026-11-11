<?php

namespace App\Filament\Resources\ApprovalPolicies\Schemas;

use App\Models\Branch;
use App\Models\User;
use App\Modules\HR\ApprovalPolicies\Enums\ApprovalMode;
use App\Modules\HR\ApprovalPolicies\Enums\ApprovalPolicyStepType;
use App\Modules\HR\ApprovalPolicies\Models\ApprovalPolicy;
use App\Modules\HR\ApprovalPolicies\Rules\UniqueApprovalPolicy;
use App\Modules\HR\ApprovalPolicies\Support\ApprovalSubjectRegistry;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
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
                                Grid::make(4)
                                    ->columnSpanFull()
                                    ->schema([
                                        TextInput::make('name')
                                            ->label(__('Name'))
                                            ->required()
                                            ->maxLength(255)
                                            ->placeholder(__('Example: Leave approval - branch manager'))
                                            ->rules([
                                                fn (Get $get, ?Model $record) => function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                                    $type = $get('approvable_type');
                                                    $appId = $get('application_type_id');

                                                    if (! $type) {
                                                        [$type, $appId] = ApprovalSubjectRegistry::decodeCombinedSubject(
                                                            $get('approval_subject_combined')
                                                        );
                                                    }

                                                    (new UniqueApprovalPolicy(
                                                        ignoreId: $record?->id,
                                                        approvableType: $type,
                                                        applicationTypeId: $appId,
                                                        branchIds: $get('branch_ids')
                                                    ))->validate($attribute, $value, $fail);
                                                },
                                            ]),

                                        Select::make('branch_ids')
                                            ->label(__('Branches'))
                                            ->placeholder('All')
                                            ->options(fn (): array => Branch::query()
                                                ->active()
                                                ->orderBy('name')
                                                ->pluck('name', 'id')
                                                ->all())
                                            ->multiple()
                                            ->searchable()
                                            ->nullable()
                                            ->dehydrateStateUsing(fn ($state) => ApprovalPolicy::normalizeBranchIds($state))
                                            ->helperText(__('Select all to apply this policy to every branch.')),

                                        Toggle::make('active')
                                            ->label(__('Active'))
                                            ->default(true)
                                            ->inline(false),

                                        Toggle::make('allow_skip_steps')
                                            ->label(__('Allow Skipping Steps'))
                                            ->helperText(__('Allow higher-step approvers to bypass pending preceding steps.'))
                                            ->default(false)
                                            ->inline(false),
                                    ]),

                                Grid::make(2)
                                    ->columnSpanFull()
                                    ->schema([
                                        Hidden::make('approvable_type'),
                                        Hidden::make('application_type_id'),

                                        Select::make('approval_subject_combined')
                                            ->label(__('Approval Subject'))
                                            ->options(fn (): array => ApprovalSubjectRegistry::combinedSubjectOptions())
                                            ->required()
                                            ->searchable()
                                            ->live()
                                            ->afterStateHydrated(function (Select $component, Get $get): void {
                                                $component->state(
                                                    ApprovalSubjectRegistry::encodeCombinedSubject(
                                                        $get('approvable_type'),
                                                        $get('application_type_id')
                                                    )
                                                );
                                            })
                                            ->afterStateUpdated(function (?string $state, Set $set): void {
                                                [$type, $appId] = ApprovalSubjectRegistry::decodeCombinedSubject($state);
                                                $set('approvable_type', $type);
                                                $set('application_type_id', $appId);
                                            })
                                            ->dehydrated(false)
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        ]),

                    Step::make(__('Workflow Steps'))
                        ->icon('heroicon-o-queue-list')
                        ->schema([
                            Repeater::make('policySteps')
                                ->label(__('Route Steps'))
                                ->relationship('policySteps')
                                ->columnSpanFull()
                                ->columns(3)
                                ->schema([
                                    Select::make('approver_type')
                                        ->label(__('Approver Type'))
                                        ->options(ApprovalPolicyStepType::options())
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
                                        ->options(fn (): array => User::query()
                                            ->orderBy('name')
                                            ->pluck('name', 'id')
                                            ->all())
                                        ->searchable()
                                        ->optionsLimit(8)
                                        ->required(fn (Get $get): bool => $get('approver_type') === ApprovalPolicyStepType::CUSTOM_USER)
                                        ->visible(fn (Get $get): bool => $get('approver_type') === ApprovalPolicyStepType::CUSTOM_USER),

                                    Select::make('approver_role_id')
                                        ->label(__('Role'))
                                        ->options(fn (): array => Role::query()
                                            ->orderBy('name')
                                            ->pluck('name', 'id')
                                            ->all())
                                        ->searchable()
                                        ->optionsLimit(8)
                                        ->required(fn (Get $get): bool => $get('approver_type') === ApprovalPolicyStepType::CUSTOM_ROLE)
                                        ->visible(fn (Get $get): bool => $get('approver_type') === ApprovalPolicyStepType::CUSTOM_ROLE),

                                    TextInput::make('manager_level')
                                        ->label(__('Manager Level'))
                                        ->numeric()
                                        ->minValue(1)
                                        ->step(1)
                                        ->required(fn (Get $get): bool => $get('approver_type') === ApprovalPolicyStepType::MANAGER_LEVEL)
                                        ->visible(fn (Get $get): bool => $get('approver_type') === ApprovalPolicyStepType::MANAGER_LEVEL),
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
}
