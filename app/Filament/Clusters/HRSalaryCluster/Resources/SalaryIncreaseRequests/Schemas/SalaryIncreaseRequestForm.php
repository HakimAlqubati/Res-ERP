<?php

declare(strict_types=1);

namespace App\Filament\Clusters\HRSalaryCluster\Resources\SalaryIncreaseRequests\Schemas;

use App\Models\Employee;
use App\Models\SalaryIncreaseRequest;
use App\Modules\HR\Payroll\Contracts\EmployeeSalaryResolverInterface;
use App\Modules\HR\SalaryIncrease\Builders\SalaryIncreasePayloadBuilder;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class SalaryIncreaseRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        $recalculate = static function (Get $get, Set $set): void {
            $oldSalary = (float) ($get('old_salary') ?? 0);
            $type = (string) ($get('increase_type') ?? SalaryIncreaseRequest::INCREASE_TYPE_FIXED);

            $value = match ($type) {
                SalaryIncreaseRequest::INCREASE_TYPE_PERCENTAGE => (float) ($get('increase_percentage') ?? 0),
                SalaryIncreaseRequest::INCREASE_TYPE_NEW_SALARY => (float) ($get('new_salary') ?? 0),
                default => (float) ($get('increase_amount') ?? 0),
            };

            $amounts = SalaryIncreasePayloadBuilder::calculateAmounts($oldSalary, $type, $value);

            $set('increase_amount', $amounts['increase_amount']);
            $set('increase_percentage', $amounts['increase_percentage']);
            $set('new_salary', $amounts['new_salary']);
        };

        $refreshOldSalaryAndRecalculate = static function (Get $get, Set $set) use ($recalculate): void {
            $employeeId = $get('employee_id');
            if (! $employeeId) {
                $set('old_salary', 0);
                $recalculate($get, $set);

                return;
            }

            $effectiveDate = $get('effective_date') ?: now()->toDateString();
            try {
                $parsedDate = Carbon::parse($effectiveDate);
            } catch (\Throwable) {
                return;
            }

            $effectiveSalary = app(EmployeeSalaryResolverInterface::class)
                ->resolveForPeriod((int) $employeeId, $parsedDate->year, $parsedDate->month);

            $set('old_salary', round($effectiveSalary, 2));
            $recalculate($get, $set);
        };

        return $schema->components([
            Fieldset::make(__('Employee & Salary'))
                ->columnSpanFull()
                ->columns(3)
                ->schema([
                    Select::make('employee_id')
                        ->label(__('lang.employee'))
                        ->relationship(
                            'employee',
                            'name',
                            modifyQueryUsing: fn (Builder $query) => $query->active()->forBranchManager()
                        )
                        ->getOptionLabelFromRecordUsing(fn (Employee $record) => "{$record->id} - {$record->name}")
                        ->searchable(['id', 'employee_no', 'name'])
                        ->preload()
                        ->disabledOn('edit')
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, Set $set, Get $get) use ($refreshOldSalaryAndRecalculate): void {
                            $refreshOldSalaryAndRecalculate($get, $set);
                        }),

                    TextInput::make('old_salary')
                        ->label(__('Current / Old Salary'))
                        ->numeric()
                        ->disabled()
                        ->dehydrated(),

                    DatePicker::make('effective_date')
                        ->label(__('Effective Date'))
                        ->default(now()->toDateString())
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, Set $set, Get $get) use ($refreshOldSalaryAndRecalculate): void {
                            $refreshOldSalaryAndRecalculate($get, $set);
                        }),
                ]),

            Fieldset::make(__('Increase Details'))
                ->columnSpanFull()
                ->columns(4)
                ->schema([
                    Select::make('increase_type')
                        ->label(__('Increase Type'))
                        ->options(SalaryIncreaseRequest::increaseTypeOptions())
                        ->default(SalaryIncreaseRequest::INCREASE_TYPE_FIXED)
                        ->required()
                        ->live()
                        ->afterStateUpdated($recalculate),

                    TextInput::make('increase_amount')
                        ->label(__('Increase Amount'))
                        ->numeric()
                        ->minValue(0.01)
                        ->live(onBlur: true)
                        ->readOnly(fn (Get $get) => $get('increase_type') !== SalaryIncreaseRequest::INCREASE_TYPE_FIXED)
                        ->required(fn (Get $get) => $get('increase_type') === SalaryIncreaseRequest::INCREASE_TYPE_FIXED)
                        ->afterStateUpdated($recalculate),

                    TextInput::make('increase_percentage')
                        ->label(__('Increase Percentage (%)'))
                        ->numeric()
                        ->minValue(0.01)
                        ->maxValue(500)
                        ->live(onBlur: true)
                        ->readOnly(fn (Get $get) => $get('increase_type') !== SalaryIncreaseRequest::INCREASE_TYPE_PERCENTAGE)
                        ->required(fn (Get $get) => $get('increase_type') === SalaryIncreaseRequest::INCREASE_TYPE_PERCENTAGE)
                        ->afterStateUpdated($recalculate),

                    TextInput::make('new_salary')
                        ->label(__('New Salary'))
                        ->numeric()
                        ->minValue(0.01)
                        ->live(onBlur: true)
                        ->readOnly(fn (Get $get) => $get('increase_type') !== SalaryIncreaseRequest::INCREASE_TYPE_NEW_SALARY)
                        ->required(fn (Get $get) => $get('increase_type') === SalaryIncreaseRequest::INCREASE_TYPE_NEW_SALARY)
                        ->afterStateUpdated($recalculate),
                ]),

            Fieldset::make(__('lang.notes'))
                ->columnSpanFull()
                ->columns(1)
                ->schema([
                    Textarea::make('notes')
                        ->label(__('lang.notes'))
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
