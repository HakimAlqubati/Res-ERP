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
use App\Models\MonthlySalaryIncreaseDetail;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
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
        // dd(getMonthOptionsBasedOnSettings());
        // dd(getMonthsArray2());
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
                        ->options(Branch::withAccess()->active()
                            ->select('id', 'name')->get()->pluck('name', 'id'))
                        ->required()

                        ->helperText('Please, choose a branch'),
                    Select::make('name')->label('Month')->hiddenOn('view')
                        ->required()
                        // ->options(function () {
                        //     $options = [];
                        //     $currentDate = new \DateTime();
                        //     for ($i = 0; $i < 12; $i++) {
                        //         $monthDate = (clone $currentDate)->sub(new \DateInterval("P{$i}M")); // Subtract months
                        //         $monthName = $monthDate->format('F Y'); // Full month name with year
                        //         $monthNameOnly = $monthDate->format('F'); // Full month name
                        //         // $monthValue = $monthDate->format('Y-m'); // Value in Y-m format

                        //         $options[$monthNameOnly] = $monthName;
                        //     }

                        //     return $options;
                        // })
                        ->options(fn() => getMonthOptionsBasedOnSettings()) // Use the helper function

                        // ->searchable()
                        ->default(now()->format('F')),
                    TextInput::make('name')->label('Title')->hiddenOn('create')->disabled(),
                    Forms\Components\DatePicker::make('payment_date')->required()
                        ->default(date('Y-m-d')),
                ]),
                Forms\Components\Textarea::make('notes')->label('Notes')->columnSpanFull(),
            ]);
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
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('payment_date')->date()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\ToggleColumn::make('approved')->toggleable(isToggledHiddenByDefault: true)->disabled(),
            ])
            ->filters([
                SelectFilter::make('branch_id')
                    ->searchable()
                    ->multiple()
                    ->label(__('lang.branch'))->options([Branch::get()->pluck('name', 'id')->toArray()]),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make()->button(),
                Tables\Actions\ViewAction::make()->button(),
                Action::make('excel_download')
                    ->button()
                    ->color('info')
                    ->icon('heroicon-o-arrow-down-on-square-stack')
                    ->action(function ($record) {
                        return static::exportExcel($record);
                    }),
                Action::make('bulk_salary_slip')
                    ->button()->label('Bulk salary slip')
                    ->color('primary') // Use primary color for bulk action
                    ->icon('heroicon-o-archive-box-arrow-down') // Icon for bulk salary slips                
                    ->form(function ($record) {
                        return static::bulkSalarySlipForm($record);
                    })

                    ->action(function ($record, $data) {
                        $employeeIds = $data['employee_ids'];
                        return static::bulkSalarySlip($record, $employeeIds);
                    }),
                Action::make('salary_slip')
                    ->button()->label('Salary slip')
                    ->color('success') // Use secondary color for single employee action
                    ->icon('heroicon-o-document-arrow-down') // Icon for employee salary slip

                    ->form(function ($record) {
                        $employeeIds = $record?->details->pluck('employee_id')->toArray();

                        return [
                            Hidden::make('month')->default($record?->month),
                            Select::make('employee_id')
                                ->required()
                                ->label('Employee')
                                ->searchable()
                                ->columns(2)
                                ->options(function () use ($employeeIds) {
                                    return Employee::whereIn('id', $employeeIds)
                                        ->select('name', 'id')
                                        ->pluck('name', 'id');
                                })
                                ->allowHtml(),


                        ];
                    })
                    ->action(function ($record, $data) {
                        $employeeId = $data['employee_id'];
                        return generateSalarySlipPdf_($employeeId, $record->id);
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
            'view' => Pages\ViewMonthSalary::route('/{record}'),
        ];
    }


    public static function exportExcel($record)
    {

        $branch = $record?->branch?->name;
        $fileName = ('Salaries of' . '-(' . $branch . ')');
        $details = $record?->details;

        $allowanceTypes = Allowance::where('is_specific', 0)->where('active', 1)->select('name', 'id')->pluck('name', 'id')->toArray();
        $specificAllowanceTypes = Allowance::where('is_specific', 1)->where('active', 1)->select('name', 'id')->pluck('name', 'id')->toArray();


        $constAllowanceTypes = MonthlySalaryIncreaseDetail::ALLOWANCE_TYPES;
        $allAllowanceTypes = $allowanceTypes + $constAllowanceTypes;
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
            foreach ($allAllowanceTypes as $keyId => $val) {
                $resAllowances[$keyId] = optional($employeeIncrease->firstWhere('type_id', $keyId))->amount ?? 00;
            }

            $resSpecificAllowances = 0;
            foreach ($specificAllowanceTypes as $sKeyId => $val) {
                $resSpecificAllowances += optional($employeeIncrease->firstWhere('type_id', $sKeyId))->amount ?? 00;
            }

            $monthlyInstallmentAdvanced = $employee?->transactions()
                ->where('transaction_type_id', 6)
                ->where('year', date('Y', strtotime($record->month)))
                ->where('month', date('m', strtotime($record->month)))

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

        return Excel::download(new SalariesExport($data, $allDeductionTypes, $allAllowanceTypes), $fileName . '.xlsx');
    }

    public static function bulkSalarySlip($record, $employeeIds)
    {
        $zipFileName = 'salary_slips.zip';
        $zipFilePath = storage_path('app/public/' . $zipFileName);
        $zip = new \ZipArchive();

        if ($zip->open($zipFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            foreach ($employeeIds as $employeeId) {
                $pdfContent = generateSalarySlipPdf($employeeId, $record->id); // Generate the PDF content
                $employeeName = Employee::find($employeeId)->name;
                $fileName = 'salary-slip_' . $employeeName . '.pdf';

                // Add the PDF content to the ZIP archive
                $zip->addFromString($fileName, $pdfContent);
            }

            // Close the ZIP archive
            $zip->close();

            // Provide the ZIP file for download
            return response()->download($zipFilePath)->deleteFileAfterSend(true);
        } else {
            throw new \Exception('Could not create ZIP file.');
        }
    }

    public static function bulkSalarySlipForm($record)
    {
        $employeeIds = $record?->details->pluck('employee_id')->toArray();
        $employeeOptions = Employee::whereIn('id', $employeeIds)
            ->select('name', 'id')
            ->pluck('name', 'id')
            ->toArray();

        return [
            Hidden::make('month')->default($record?->month),
            Forms\Components\CheckboxList::make('employee_ids')
                ->searchable()
                ->required()->columns(3)
                ->label('Select Employees')->bulkToggleable()
                ->options($employeeOptions)
                ->default(array_keys($employeeOptions)),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::query()
            ->withBranch()->count();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withBranch()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
