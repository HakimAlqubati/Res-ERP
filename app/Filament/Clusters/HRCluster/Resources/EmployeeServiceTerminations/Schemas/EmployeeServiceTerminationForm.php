<?php

namespace App\Filament\Clusters\HRCluster\Resources\EmployeeServiceTerminations\Schemas;

use App\Models\Employee;
use App\Rules\HR\Employee\CanRequestTerminationRule;
use App\Rules\HR\Employee\EmployeeFinancialClearanceRule;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class EmployeeServiceTerminationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Fieldset::make(__('lang.termination_details') ?? 'Termination Details')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('employee_id')
                            ->label(__('lang.employee'))
                            ->relationship('employee', 'name', modifyQueryUsing: fn (\Illuminate\Database\Eloquent\Builder $query) => $query->active()->forBranchManager())
                            ->getOptionLabelFromRecordUsing(fn (Employee $record) => "{$record->id} - {$record->name}")
                            ->searchable(['id', 'employee_no', 'name'])
                            ->optionsLimit(5)
                            ->preload()
                            ->required()
                            ->disabledOn('edit')
                            ->live()
                            ->rules(fn (string $operation): array => $operation === 'create'
                                ? [new CanRequestTerminationRule()]
                                : []
                            ),
                        DatePicker::make('termination_date')
                            ->label(__('lang.termination_date'))
                            ->required()
                            ->default(now())
                            ->live()
                            ->rules([
                                fn (Get $get) => new EmployeeFinancialClearanceRule(
                                    $get('employee_id') ? Employee::find($get('employee_id')) : null
                                ),
                            ]),
                        Textarea::make('termination_reason')
                            ->label(__('lang.termination_reason'))
                            ->columnSpanFull()
                            ->required(),
                        Textarea::make('notes')
                            ->label(__('lang.notes'))
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
