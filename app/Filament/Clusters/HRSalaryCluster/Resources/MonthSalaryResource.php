<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources;

use App\Exports\SalariesExport;
use App\Filament\Clusters\HRSalaryCluster;
use App\Filament\Clusters\HRSalaryCluster\Resources\MonthSalaryResource\Pages;
use App\Filament\Clusters\HRSalaryCluster\Resources\MonthSalaryResource\RelationManagers\DetailsRelationManager;
use App\Models\Allowance;
use App\Models\Branch;
use App\Models\Deduction;
use App\Models\Employee;
use App\Models\MonthlySalaryDeductionsDetail;
use App\Models\MonthSalary;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;
use Maatwebsite\Excel\Facades\Excel;

class MonthSalaryResource extends Resource
{
    protected static ?string $model = MonthSalary::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = HRSalaryCluster::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return 'Payroll';
    }
    public static function getPluralLabel(): ?string
    {
        return 'Payroll';
    }

    public static function getLabel(): ?string
    {
        return 'Payroll';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Fieldset::make()->label('Set Branch, Month and payment date')->columns(3)->schema([
                    TextInput::make('note_that')->label('Note that!')->columnSpan(3)->hiddenOn('view')
                        ->disabled()
                    // ->extraAttributes(['class' => 'text-red-600'])
                        ->suffixIcon('heroicon-o-exclamation-triangle')
                        ->suffixIconColor('warning')
                    // ->color(Color::Red)
                        ->default('Employees who have not had their work periods added, will not appear on the payroll.'),
                    Select::make('branch_id')->label('Choose branch')
                        ->disabledOn('view')
                        ->options(Branch::where('active', 1)->select('id', 'name')->get()->pluck('name', 'id'))
                        ->required()
                        
                        
                        ->helperText('Please, choose a branch'),
                    Select::make('name')->label('Month')->hiddenOn('view')
                        ->required()
                        ->options(function () {
                            // Get the array of months
                            $months = getMonthsArray();

                            // Map the months to a key-value pair with month names
                            return collect($months)->mapWithKeys(function ($month, $key) {
                                return [$key => $month['name']]; // Using month key as the option key
                            });
                        })
                    // ->searchable()
                        ->default(now()->format('F'))
                    ,
                    TextInput::make('name')->label('Title')->hiddenOn('create')->disabled(),
                    Forms\Components\DatePicker::make('payment_date')->required()
                        ->default(date('Y-m-d'))
                    ,
                ]),
                Forms\Components\Textarea::make('notes')->label('Notes')->columnSpanFull(),
            ]);
    }

   
   
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function table(Table $table): Table
    {
    
        return $table
        ->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Title')->searchable(),
                Tables\Columns\TextColumn::make('notes')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('branch.name')->label('Branch')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('createdBy.name')->label('Created by')->searchable()->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                ,
                Tables\Columns\TextColumn::make('payment_date')->date()
                    ->toggleable(isToggledHiddenByDefault: true)
                ,
                Tables\Columns\ToggleColumn::make('approved')->toggleable(isToggledHiddenByDefault: true)->disabled(),
            ])
            ->filters([
                SelectFilter::make('branch_id')
                    ->searchable()
                    ->multiple()
                    ->label(__('lang.branch'))->options([Branch::get()->pluck('name', 'id')->toArray()]),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ViewAction::make(),
                Action::make('excel_download')
                    ->button()
                    ->color('info')
                    ->icon('heroicon-o-arrow-down-on-square-stack')
                    ->action(function ($record) {
                        return static::exportExcel($record);
                    }),
                Action::make('salary_slip')
                    ->button()
                    ->color('success')
                    ->icon('heroicon-o-newspaper')
                    ->form(function ($record) {
                        $employeeIds = $record?->details->pluck('employee_id')->toArray();

                        return [
                            Hidden::make('month')->default($record?->month),
                            Select::make('employee_id')
                                ->required()
                                ->label('Employee')->searchable()
                                ->helperText('Search employee to get his payslip')
                                ->options(Employee::whereIn('id', $employeeIds)->select('name', 'id')->pluck('name', 'id')),
                        ];
                    })
                    ->action(function ($record, $data) {
                        $month = $data['month'];
                        
                        $employeeId = $data['employee_id'];

                        // Generate the URL using the route with parameters
                        $url = url("/to_test_salary_slip/{$employeeId}/{$record->id}");

                        // Redirect to the generated URL
                        return redirect()->away($url);

                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            DetailsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMonthSalaries::route('/'),
            'create' => Pages\CreateMonthSalary::route('/create'),
            // 'edit' => Pages\EditMonthSalary::route('/{record}/edit'),
            'view' => Pages\ViewMonthSalary::route('/{record}'),
        ];
    }

    // public static function canDelete(Model $record): bool
    // {
    //     return false;
    // }

    // public static function canDeleteAny(): bool
    // {
    //     return false;
    // }

    public static function exportExcel($record)
    {

        $branch = $record?->branch?->name;
        $fileName = ('Salaries of' . '-(' . $branch . ')');
        $details = $record?->details;

        $allowanceTypes = Allowance::where('is_specific', 0)->where('active', 1)->select('name', 'id')->pluck('name', 'id')->toArray();
        $specificAllowanceTypes = Allowance::where('is_specific', 1)->where('active', 1)->select('name', 'id')->pluck('name', 'id')->toArray();

        $deducationTypes = Deduction::where('is_specific', 0)->where('active', 1)->select('name', 'id')->pluck('name', 'id')->toArray();
        $specificDeducationTypes = Deduction::where('is_specific', 1)->where('active', 1)->select('name', 'id')->pluck('name', 'id')->toArray();
        $constDeducationTypes = MonthlySalaryDeductionsDetail::DEDUCTION_TYPES;

        $allDeductionTypes = $deducationTypes + $constDeducationTypes;

        $deducationDetails = $record?->deducationDetails;
        $increaseDetails = $record->increaseDetails;
 
        
        $data = [];
        foreach ($details as $key => $value) {
            $employee = $value->employee;
            $empId = $employee->id;

            $employeeDeductions = $deducationDetails->where('employee_id', $empId);
            $employeeIncrease = $increaseDetails->where('employee_id', $empId);

            $resDeducation = [];
            foreach ($allDeductionTypes as $deductionId => $deductionType) {
                // Find the deduction amount for the current deduction type, or return null if not found
                $deductionAmount = optional($employeeDeductions->firstWhere('deduction_id', $deductionId))->deduction_amount ?? 00;
                // Store the result, using a unique key format based on employee ID and deduction ID
                $resDeducation[$deductionId] = $deductionAmount;
            }
            $resSpecificDeducation = 0;
            foreach ($specificDeducationTypes as $sDeductionId => $sDeductionType) {
                // Find the deduction amount for the current deduction type, or return null if not found
                $sDeductionAmount = optional($employeeDeductions->firstWhere('deduction_id', $sDeductionId))->deduction_amount ?? 00;
                // Store the result, using a unique key format based on employee ID and deduction ID
                $resSpecificDeducation += $sDeductionAmount;
            }

            $resAllowances = [];
            foreach ($allowanceTypes as $keyId => $val) {
                $resAllowances[$keyId] = optional($employeeIncrease->firstWhere('type_id', $keyId))->amount ?? 00;
            }

            $resSpecificAllowances = 0;
            foreach ($specificAllowanceTypes as $sKeyId => $val) {
                $resSpecificAllowances += optional($employeeIncrease->firstWhere('type_id', $sKeyId))->amount ?? 00;
            }

            $monthlyInstallmentAdvanced = $employee?->transactions()
                ->where('transaction_type_id', 6)
                ->where('year', date('Y',strtotime($record->month)))
                ->where('month',  date('m',strtotime($record->month)))
            
                ?->first()?->latest('id')?->first()?->amount;
            $data[] = [
                'employee_id' => $employee?->id,
                'employee_no' => $employee?->employee_no,
                'employee_name' => $value?->employee?->name,
                'job_title' => $value?->employee?->job_title,
                'branch' => $branch,
                'basic_salary' => $value?->basic_salary,
                'overtime_hours' => $value?->overtime_hours,
                'total_incentives' => $value?->total_incentives,
                'total_allowances' => $value?->total_allowances,
                'total_deductions' => $value?->total_deductions,
                'advanced_installment' => $monthlyInstallmentAdvanced,
                'res_deducation' => $resDeducation,
                'res_allowances' => $resAllowances,
                'res_specific_deducation' => $resSpecificDeducation,
                'res_specific_allowances' => $resSpecificAllowances,
                'bonus' => $value->total_other_adding,
                'net_salary' => $value->net_salary,
            ];
        }

        return Excel::download(new SalariesExport($data, $allDeductionTypes, $allowanceTypes), $fileName . '.xlsx');
    }
}
