<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources\MonthSalaryResource\Pages;

use App\Filament\Clusters\HRSalaryCluster\Resources\MonthSalaryResource;
use App\Models\Employee;
use Filament\Resources\Pages\CreateRecord;

class CreateMonthSalary extends CreateRecord
{
    protected static string $resource = MonthSalaryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {

        $monthsArray = getMonthsArray();

        if (array_key_exists($data['name'], $monthsArray)) {
            $data['start_month'] = $monthsArray[$data['name']]['start_month'];
            $data['end_month'] = $monthsArray[$data['name']]['end_month'];
            $data['name'] = 'Salary of month (' . $monthsArray[$data['name']]['name'] . ')';
        }

        $data['created_by'] = auth()->user()->id;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $branchEmployees = Employee::where('active', 1)->where('branch_id', $this->record->branch_id)->select('id')->get();

        foreach ($branchEmployees as $employee) {
            $calculateSalary = calculateMonthlySalary($employee->id, $this->record->end_month);
            if ($calculateSalary != 'no_periods') {
                $this->record->details()->create([
                    'employee_id' => $employee->id,
                    'basic_salary' => $calculateSalary['details']['basic_salary'],
                    'total_deductions' => $calculateSalary['details']['total_deductions'],
                    'total_allowances' => $calculateSalary['details']['total_allowances'],
                    'total_incentives' => $calculateSalary['details']['total_monthly_incentives'],
                    'overtime_hours' => $calculateSalary['details']['overtime_hours'],
                    'overtime_pay' => $calculateSalary['details']['overtime_hours'],
                    'net_salary' => $calculateSalary['details']['overtime_hours'],
                ]);
            }
        }

    }
}
