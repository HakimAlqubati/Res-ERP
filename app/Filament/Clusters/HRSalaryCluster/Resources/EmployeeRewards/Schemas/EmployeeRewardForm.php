<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources\EmployeeRewards\Schemas;

use App\Models\Employee;
use App\Models\EmployeeReward;
use App\Models\MonthlyIncentive;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Fieldset;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EmployeeRewardForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Fieldset::make()->columnSpanFull()->label('Reward/Bonus Information')->columns(3)->schema([
                    DatePicker::make('date')
                        ->label('Date')
                        ->default(now()->toDateString())
                        ->required()
                        ->live()
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
                TextInput::make('year')->hidden(),
                TextInput::make('month')->hidden(),
            ]);
    }
}
