<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources\PayrollResource;

use App\Models\Branch;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;

class PayrollForm
{
    /**
     * Get the form schema for PayrollResource.
     */
    public static function getSchema(): array
    {
        return [
            Fieldset::make()->columnSpanFull()->label('Set Branch, Month and payment date')->columns(3)->schema([
                TextInput::make('note_that')->label('Note that!')->columnSpan(3)->hiddenOn('view')
                    ->disabled()
                    ->suffixIcon('heroicon-o-exclamation-triangle')
                    ->suffixIconColor('warning')
                    ->default('Employees who have not had their work periods added, will not appear on the payroll.'),
                Select::make('branch_id')->label('Choose branch')
                    ->disabledOn('view')->searchable()
                    ->options(Branch::branches()
                        ->forBranchManager('id')
                        ->select('id', 'name')
                        ->get()
                        ->pluck('name', 'id'))
                    ->required()
                    ->helperText('Please, choose a branch'),
                Select::make('name')->label('Month')->hiddenOn('view')
                    ->required()
                    ->options(fn() => getMonthOptionsBasedOnSettings())
                    ->default(now()->format('F'))
                    ->rule(function (callable $get) {
                        return function (string $attribute, $value, \Closure $fail) use ($get) {
                            $branchId = $get('branch_id');
                            if (! $branchId) {
                                return;
                            }

                            [$monthName, $year] = explode(' ', $value);
                            $monthNumber = \Carbon\Carbon::parse($monthName)->month;

                            $exists = \App\Models\PayrollRun::query()
                                ->where('branch_id', $branchId)
                                ->where('year', (int) $year)
                                ->where('month', (int) $monthNumber)
                                ->withTrashed()
                                ->first();

                            if ($exists) {
                                if ($exists->trashed()) {
                                    $fail(__('Payroll for this branch and month already exists in the recycle bin. Please restore or permanently delete it before creating a new one.'));
                                } else {
                                    $fail(__('Payroll for this branch and month already exists. You cannot create a duplicate.'));
                                }
                            }
                        };
                    }),
                TextInput::make('name')->label('Title')->hiddenOn('create')->disabled(),
                DatePicker::make('pay_date')->required()
                    ->default(date('Y-m-d')),
            ]),
            Textarea::make('notes')->label('Notes')->columnSpanFull(),
        ];
    }
}
