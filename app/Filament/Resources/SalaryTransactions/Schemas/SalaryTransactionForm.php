<?php

namespace App\Filament\Resources\SalaryTransactions\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class SalaryTransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            // 🟢 بيانات الموظف والراتب
            Fieldset::make(__('Employee & Payroll'))->columnSpanFull()
                ->schema([
                    Grid::make(3)->columnSpanFull()->schema([
                        Select::make('employee_id')
                            ->label(__('Employee'))
                            ->options(fn() => \App\Models\Employee::active()
                                ->orderBy('name')
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive(),

                        Select::make('payroll_id')
                            ->label(__('Payroll'))
                            ->options(function (callable $get) {
                                $employeeId = $get('employee_id');
                                if (! $employeeId) {
                                    return [];
                                }

                                return \App\Models\Payroll::query()
                                    ->where('employee_id', $employeeId)
                                    ->orderByDesc('year')
                                    ->orderByDesc('month')
                                    ->get()
                                    ->mapWithKeys(function ($payroll) {
                                        $label = $payroll->year . '-' . str_pad($payroll->month, 2, '0', STR_PAD_LEFT);
                                        return [$payroll->id => $label];
                                    });
                            })
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->reactive()
                            ->disabled(fn(callable $get) => ! $get('employee_id')),
                        Select::make('status')
                            ->label(__('Status'))
                            ->options([
                                'pending'  => __('Pending'),
                                'approved' => __('Approved'),
                                'rejected' => __('Rejected'),
                            ])
                            ->default('pending')
                            ->required(),
                    ]),
                ]),

            // 🟢 تفاصيل العملية
            Fieldset::make(__('Transaction Details'))->columnSpanFull()
                ->schema([

                    Grid::make(3)->columnSpanFull()->schema([
                        Select::make('type')
                            ->label(__('Type'))
                            ->options(\App\Models\SalaryTransaction::typeOptions())
                            ->searchable()
                            ->required()
                            ->reactive()->reactive()
                            ->afterStateUpdated(function (callable $set, $state) {
                                // اضبط قيمة operation تلقائياً عند تغيير النوع
                                if ($state) {
                                    $set('sub_type', null); // إعادة تعيين النوع الفرعي عند تغيير النوع الرئيسي
                                    $set('operation', \App\Models\SalaryTransaction::operationForType($state));
                                }
                            }),

                        Select::make('sub_type')
                            ->label(__('Sub Type'))
                            ->options(
                                fn(callable $get) =>
                                \App\Models\SalaryTransaction::subTypesForType($get('type'))
                            )
                            ->searchable()
                            ->nullable()
                            ->reactive()
                            ->rule(function (callable $get) {
                                return function (string $attribute, $value, \Closure $fail) use ($get) {
                                    $exists = \App\Models\SalaryTransaction::query()
                                        ->where('employee_id', $get('employee_id'))
                                        ->where('payroll_id', $get('payroll_id'))
                                        ->where('year', optional(\App\Models\Payroll::find($get('payroll_id')))->year)
                                        ->where('month', optional(\App\Models\Payroll::find($get('payroll_id')))->month)
                                        ->where('type', $get('type'))
                                        ->where('sub_type', $value)
                                        ->exists();

                                    if ($exists) {
                                        $fail(__('This transaction already exists for this employee in the same month and type.'));
                                    }
                                };
                            })
                            ->disabled(fn(callable $get) => !$get('type')),

                        Select::make('operation')
                            ->label(__('Operation'))
                            ->options([
                                '+' => __('Addition'),
                                '-' => __('Deduction'),
                            ])
                            ->required(),
                    ]),
                    Grid::make(2)->columnSpanFull()->schema([
                        DatePicker::make('date')
                            ->label(__('Date'))
                            ->default(now())
                            ->required(),


                        TextInput::make('amount')
                            ->label(__('Amount'))
                            ->numeric()
                            ->prefix(fn() => getDefaultCurrency())
                            ->required(),
                    ]),



                    Grid::make(2)->columnSpanFull()->hidden()->schema([
                        TextInput::make('qty')
                            ->label(__('Quantity'))
                            ->numeric()
                            ->nullable(),

                        TextInput::make('rate')
                            ->label(__('Rate'))
                            ->numeric()
                            ->nullable(),
                    ]),
                ]),


            Textarea::make('description')
                ->label(__('Description'))->columnSpanFull()
                ->rows(3)
                ->required(),

        ]);
    }
}
