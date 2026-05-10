<?php

namespace App\Filament\Resources\ApprovalPolicies\Schemas;

use App\Models\AdvanceWage;
use App\Models\Branch;
use App\Models\EmployeeApplicationV2;
use App\Models\EmployeeOvertime;
use App\Models\User;
use App\Modules\HR\ApprovalPolicies\Enums\ApprovalMode;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class ApprovalPolicyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
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

                Section::make(__('Approval Route'))
                    ->schema([
                        Grid::make(3)->schema([
                            Select::make('mode')
                                ->label(__('Approval Mode'))
                                ->options(self::modeOptions())
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (?string $state, Set $set): void {
                                    if ($state !== ApprovalMode::MANAGER_CHAIN) {
                                        $set('levels', null);
                                    }

                                    if ($state !== ApprovalMode::CUSTOM_USERS) {
                                        $set('custom_approver_user_ids', null);
                                    }
                                }),

                            TextInput::make('levels')
                                ->label(__('Manager Levels'))
                                ->numeric()
                                ->minValue(1)
                                ->step(1)
                                ->nullable()
                                ->helperText(__('Leave empty to walk the full manager chain.'))
                                ->visible(fn (Get $get): bool => $get('mode') === ApprovalMode::MANAGER_CHAIN),

                            Select::make('custom_approver_user_ids')
                                ->label(__('Custom Approvers'))
                                ->options(fn (): array => User::query()
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all())
                                ->multiple()
                                ->searchable()
                                ->preload()
                                ->required(fn (Get $get): bool => $get('mode') === ApprovalMode::CUSTOM_USERS)
                                ->visible(fn (Get $get): bool => $get('mode') === ApprovalMode::CUSTOM_USERS),
                        ]),
                    ]),

                Fieldset::make(__('Advanced'))
                    ->schema([
                        TextInput::make('final_handler')
                            ->label(__('Final Approval Handler'))
                            ->maxLength(255)
                            ->placeholder('App\\Modules\\HR\\ApprovalPolicies\\Handlers\\...')
                            ->helperText(__('Optional. Leave empty to use the default handler for this subject.'))
                            ->columnSpanFull(),
                    ])
                    ->columns(1),
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

    private static function modeOptions(): array
    {
        return [
            ApprovalMode::DIRECT_MANAGER => __('Direct Manager'),
            ApprovalMode::BRANCH_MANAGER => __('Branch Manager'),
            ApprovalMode::MANAGER_CHAIN => __('Manager Chain'),
            ApprovalMode::CUSTOM_USERS => __('Custom Users'),
        ];
    }
}
