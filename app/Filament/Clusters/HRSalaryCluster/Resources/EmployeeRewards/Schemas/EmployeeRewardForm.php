<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources\EmployeeRewards\Schemas;

use App\Models\Employee;
use App\Models\EmployeeReward;
use App\Models\MonthlyIncentive;
use App\Rules\HR\Payroll\PayrollLockRule;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Components\Fieldset;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class EmployeeRewardForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Fieldset::make()->columnSpanFull()->label('Reward/Bonus Information')->columns(4)->schema([
                    Select::make('month')
                        ->label('Month')
                        ->options([
                            1 => 'January',
                            2 => 'February',
                            3 => 'March',
                            4 => 'April',
                            5 => 'May',
                            6 => 'June',
                            7 => 'July',
                            8 => 'August',
                            9 => 'September',
                            10 => 'October',
                            11 => 'November',
                            12 => 'December',
                        ])
                        ->live()
                        ->required()
                        ->rules([
                            fn (Get $get) => new PayrollLockRule(
                                $get('employee_id'),
                                (int) ($get('year') ?: ($get('date') ? \Carbon\Carbon::parse($get('date'))->year : now()->year)),
                                (int) $get('month'),
                            ),
                        ]),

                    DatePicker::make('date')
                        ->label('Date')
                        ->default(now()->toDateString())
                        ->required()
                        ->live()
                        ->visible(fn ($get) => filled($get('month')))
                        ->afterStateUpdated(function ($set, $state) {
                            if ($state) {
                                $date = \Carbon\Carbon::parse($state);
                                $set('year', $date->year);
                                $set('month', $date->month);
                            }
                        }),

                    Select::make('employee_id')
                        ->label(__('Employee'))
                        ->relationship('employee', 'name')
                        ->searchable()
                        ->preload()
                        ->live()
                        ->required(),

                    Select::make('incentive_id')
                        ->label('Reward Type')
                        ->options(MonthlyIncentive::query()->where('active', true)->pluck('name', 'id'))
                        ->required()
                        ->searchable(),
                ]),

                Fieldset::make()->label('Financial Details')->columnSpanFull()->columns(2)->schema([
                    TextInput::make('reward_amount')
                        ->label('Amount')
                        ->numeric()
                        ->prefix('$')
                        ->required(),

                    Textarea::make('reason')
                        ->label('Reason / Description')
                        ->placeholder('Explain why this reward is being given...')
                        ->required()
                        ->rows(2),
                ]),

                // Hidden fields for automated payroll targeting
                TextInput::make('year')
                    ->default(now()->year)
                    ->hidden(),
            ]);
    }
}
