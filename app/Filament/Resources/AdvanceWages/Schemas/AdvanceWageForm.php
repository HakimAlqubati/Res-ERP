<?php

namespace App\Filament\Resources\AdvanceWages\Schemas;

use App\Models\AdvanceWage;
use App\Rules\HR\Payroll\AdvanceWageLimitRule;
use App\Rules\HR\Payroll\PayrollLockRule;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class AdvanceWageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Select::make('employee_id')
                    ->label(__('lang.employee'))
                    ->relationship('employee', 'name')
                    ->searchable()
                    ->required()
                    ->columnSpanFull()
                    ->live(),

                Grid::make(3)->schema([
                    TextInput::make('amount')
                        ->label(__('Amount'))
                        ->numeric()
                        ->minValue(0.01)
                        ->required()
                        ->live(onBlur: true)
                        ->rules([
                            fn(Get $get, $record) => new AdvanceWageLimitRule(
                                $get('employee_id'),
                                (int) \Carbon\Carbon::parse($get('wage_month') ?: now())->year,
                                (int) \Carbon\Carbon::parse($get('wage_month') ?: now())->month,
                                $record?->id,
                            ),
                            fn(Get $get) => new PayrollLockRule(
                                $get('employee_id'),
                                (int) \Carbon\Carbon::parse($get('wage_month') ?: now())->year,
                                (int) \Carbon\Carbon::parse($get('wage_month') ?: now())->month,
                            ),
                        ])
                        ->columnSpan(1),

                    TextInput::make('wage_month')
                        // ->label(__('lang.month'))
                        ->type('month')
                        ->default(now()->format('Y-m'))
                        ->required()
                        ->live()
                        ->afterStateHydrated(function ($component, $record) {
                            if ($record && $record->year && $record->month) {
                                $component->state(sprintf('%04d-%02d', $record->year, $record->month));
                            }
                        })
                        ->dehydrated(false)
                        ->columnSpan(1),

                    DatePicker::make('date')
                        ->label(__('Date'))
                        ->default(now()->toDateString())
                        ->required()
                        ->live()
                        ->rules([
                            fn(Get $get) => new PayrollLockRule(
                                $get('employee_id'),
                                (int) \Carbon\Carbon::parse($get('wage_month') ?: now())->year,
                                (int) \Carbon\Carbon::parse($get('wage_month') ?: now())->month,
                            ),
                        ])
                        ->native(false)
                        ->displayFormat('Y-m-d')
                        ->columnSpan(1),

                    Hidden::make('year')
                        ->dehydrateStateUsing(fn (Get $get) => $get('wage_month') ? (int) \Carbon\Carbon::parse($get('wage_month'))->year : null),

                    Hidden::make('month')
                        ->dehydrateStateUsing(fn (Get $get) => $get('wage_month') ? (int) \Carbon\Carbon::parse($get('wage_month'))->month : null),

                ])->columnSpanFull(),

                Grid::make(3)->schema([
                    Select::make('payment_method')
                        ->label(__('lang.payment_method'))
                        ->options(AdvanceWage::paymentMethods())
                        ->default(AdvanceWage::PAYMENT_METHOD_CASH)
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                            if ($state === AdvanceWage::PAYMENT_METHOD_BANK_TRANSFER && $get('employee_id')) {
                                $employee = \App\Models\Employee::find($get('employee_id'));
                                if ($employee) {
                                    $set('bank_account_number', $employee->bank_account_number);
                                }
                            }
                        })
                        ->columnSpan(1),

                    TextInput::make('bank_account_number')
                        ->label(__('lang.bank_account_number'))
                        ->visible(fn(Get $get) => $get('payment_method') === AdvanceWage::PAYMENT_METHOD_BANK_TRANSFER)
                        ->required(fn(Get $get) => $get('payment_method') === AdvanceWage::PAYMENT_METHOD_BANK_TRANSFER)
                        ->columnSpan(1),

                    TextInput::make('transaction_number')
                        ->label(__('lang.transaction_number'))
                        ->visible(fn(Get $get) => $get('payment_method') === AdvanceWage::PAYMENT_METHOD_BANK_TRANSFER)
                        ->required(fn(Get $get) => $get('payment_method') === AdvanceWage::PAYMENT_METHOD_BANK_TRANSFER)
                        ->columnSpan(1),
                ])->columnSpanFull(),

                TextInput::make('reason')
                    ->label(__('Reason'))
                    ->maxLength(255)->required()
                    ->columnSpanFull(),

            ])->columns(2);
    }
}
