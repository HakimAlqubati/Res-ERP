<?php

namespace App\Filament\Clusters\HRCluster\Resources\EmployeeServiceTerminations\Schemas;

use App\Models\Employee;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class EmployeeServiceTerminationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('employee_id')
                    ->label(__('lang.employee'))
                    ->relationship('employee', 'name')
                    ->getOptionLabelFromRecordUsing(fn (Employee $record) => "{$record->id} - {$record->name}")
                    ->searchable(['id', 'employee_no', 'name'])
                    ->optionsLimit(5)
                    ->preload()
                    ->required()
                    ->live(),
                DatePicker::make('termination_date')
                    ->label(__('lang.termination_date'))
                    ->required()
                    ->default(now())
                    ->live()
                    ->columnSpanFull()
                    ->rules([
                        fn (Get $get) => function (string $attribute, $value, Closure $fail) use ($get) {
                            $employeeId = $get('employee_id');
                            if (!$employeeId) {
                                return;
                            }

                            $employee = Employee::find($employeeId);
                            if (!$employee) {
                                return;
                            }

                            $unpaidBalance = (float) $employee->advancedInstallments()
                                ->where('is_paid', false)
                                ->sum('installment_amount');

                            if ($unpaidBalance > 0) {
                                $fail(__('lang.cannot_process_financial_clearance', [
                                    'amount' => number_format($unpaidBalance, 2),
                                ]) ?: 'Cannot process financial clearance. The employee has outstanding advance installments amounting to: '.number_format($unpaidBalance, 2));
                            }
                        },
                    ]),
                Textarea::make('termination_reason')
                    ->label(__('lang.termination_reason'))
                    ->columnSpanFull()
                    ->required(),
                Textarea::make('notes')
                    ->label(__('lang.notes'))
                    ->columnSpanFull(),
            ]);
    }
}
