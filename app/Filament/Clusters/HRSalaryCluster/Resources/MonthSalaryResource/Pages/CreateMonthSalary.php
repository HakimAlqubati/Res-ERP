<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources\MonthSalaryResource\Pages;

use App\Filament\Clusters\HRSalaryCluster\Resources\MonthSalaryResource;
use App\Models\Employee;
use App\Models\MonthlySalaryDeductionsDetail;
use App\Models\MonthlySalaryIncreaseDetail;
use Carbon\Carbon;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateMonthSalary extends CreateRecord
{
    protected static string $resource = MonthSalaryResource::class;
    protected ?bool $hasDatabaseTransactions = true;

    protected function mutateFormDataBeforeCreate(array $data): array
    {

        $monthsArray = getMonthsArray();

       
        if (array_key_exists($data['name'], $monthsArray)) {
            $data['start_month'] = $monthsArray[$data['name']]['start_month'];
            $monthYear = Carbon::parse($data['start_month'])->format('Y-m');

            $data['end_month'] = $monthsArray[$data['name']]['end_month'];
            $data['name'] = 'Salary of month (' . $monthsArray[$data['name']]['name'] . ')';
            $data['month'] = $monthYear;
        }

        $data['created_by'] = auth()->user()->id;

        return $data;
    }

    protected function afterCreate(): void
    {
        $branchEmployees = Employee::where('active', 1)
            ->where('branch_id', $this->record->branch_id)
            ->select('id')
            ->get();

        foreach ($branchEmployees as $employee) {
            // Begin a transaction
            DB::beginTransaction();

            try {
                $calculateSalary = calculateMonthlySalaryV2($employee->id, $this->record->end_month);
                $specificDeducation = $generalDeducation = $specificAllowances = $generalAllowances = [];

                if ($calculateSalary === 'no_periods') {
                    Log::warning("No periods found for employee ID: {$employee->id}");
                    DB::rollBack();
                    continue;
                }

                // Set deductions and allowances if available
                $specificDeducation = $calculateSalary['details']['deducation_details']['specific_deducation'] ?? [];
                $generalDeducation = $calculateSalary['details']['deducation_details']['general_deducation'] ?? [];
                $specificAllowances = $calculateSalary['details']['adding_details']['specific_allowances'] ?? [];
                $generalAllowances = $calculateSalary['details']['adding_details']['general_allowances'] ?? [];
               if(isset($calculateSalary['details'])){
                   $this->record->details()->create([
                       'employee_id' => $employee->id,
                       'basic_salary' => $calculateSalary['details']['basic_salary'],
                       'total_deductions' => $calculateSalary['details']['total_deducation'],
                       'total_allowances' => $calculateSalary['details']['total_allowances'],
                       'total_incentives' => $calculateSalary['details']['total_monthly_incentives'],
                       'total_other_adding' => $calculateSalary['details']['total_other_adding'],
                       'net_salary' => $calculateSalary['net_salary'],
                       'total_absent_days' => $calculateSalary['details']['total_absent_days'],
                       'total_late_hours' => $calculateSalary['details']['total_late_hours'],
                       'overtime_hours' => $calculateSalary['details']['overtime_hours'],
                       'overtime_pay' => $calculateSalary['net_salary']['overtime_pay'] ?? 0,
                   ]);
               }
                // Try to create salary details

                // Create allowance and deduction details
                $this->createAllowanceDetails($generalAllowances, $employee, false);
                $this->createAllowanceDetails($specificAllowances, $employee, true);
                $this->createDeductionDetails($specificDeducation, $employee, true);
                $this->createDeductionDetails($generalDeducation, $employee, false);

                if(isset($calculateSalary['details']['deduction_for_absent_days']) && $calculateSalary['details']['deduction_for_absent_days'] > 0){
                    $this->record->deducationDetails()->create([
                        'employee_id' => $employee->id,
                        
                        'deduction_id' => MonthlySalaryDeductionsDetail::ABSENT_DAY_DEDUCTIONS,
                        'deduction_name' => MonthlySalaryDeductionsDetail::DEDUCTION_TYPES[MonthlySalaryDeductionsDetail::ABSENT_DAY_DEDUCTIONS],
                        'deduction_amount' => $calculateSalary['details']['deduction_for_absent_days'],
                    ]);
                }
                if(isset($calculateSalary['details']['deduction_for_late_hours']) && $calculateSalary['details']['deduction_for_late_hours'] > 0){
                    $this->record->deducationDetails()->create([
                        'employee_id' => $employee->id,
                        
                        'deduction_id' => MonthlySalaryDeductionsDetail::LATE_HOUR_DEDUCTIONS,
                        'deduction_name' => MonthlySalaryDeductionsDetail::DEDUCTION_TYPES[MonthlySalaryDeductionsDetail::LATE_HOUR_DEDUCTIONS],
                        'deduction_amount' => $calculateSalary['details']['deduction_for_late_hours'],
                    ]);
                }
                // Commit the transaction if all is successful
                DB::commit();

            } catch (\Exception $e) {
                // Rollback the transaction on any failure
                DB::rollBack();
                Log::error("Transaction failed for employee ID: {$employee->id}", ['exception' => $e->getMessage()]);
            }
        }
    }

    private function createAllowanceDetails(array $allowances, $employee, bool $isSpecific): void
    {
        foreach ($allowances as $value) {
            try {
                if ($allowances['result'] > 0) {
                    $this->record->increaseDetails()->create([
                        'employee_id' => $employee->id,
                        'type' => MonthlySalaryIncreaseDetail::TYPE_ALLOWANCE,
                        'type_id' => $value['id'],
                        'is_specific_employee' => $isSpecific ? 1 : 0,
                        'name' => $value['name'],
                        'amount' => $value['allowance_amount'],
                    ]);
                }
            } catch (\Exception $e) {
                Log::error("Failed to create allowance details for employee ID: {$employee->id}", ['exception' => $e->getMessage()]);
            }
        }
    }

    private function createDeductionDetails(array $deductions, $employee, bool $isSpecific): void
    {
        foreach ($deductions as $value) {
            try {
                if ($deductions['result'] > 0) {
                    $this->record->deducationDetails()->create([
                        'employee_id' => $employee->id,
                        'is_specific_employee' => $isSpecific ? 1 : 0,
                        'deduction_id' => $value['id'],
                        'deduction_name' => $value['name'],
                        'deduction_amount' => $value['deduction_amount'],
                        'is_percentage' => $value['is_percentage'],
                        'amount_value' => $value['amount_value'],
                        'percentage_value' => $value['percentage_value'],
                    ]);
                }
            } catch (\Exception $e) {
                Log::error("Failed to create deduction details for employee ID: {$employee->id}", ['exception' => $e->getMessage()]);
            }
        }
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function getTitle(): string | Htmlable
    {
        if (filled(static::$title)) {
            return static::$title;
        }

        return __('filament-panels::resources/pages/create-record.title', [
            'label' => 'Generate',
        ]);
    }
}
 