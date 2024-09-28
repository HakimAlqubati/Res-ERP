<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\EmployeeOvertimeResource\Pages;

use App\Filament\Clusters\HRAttenanceCluster\Resources\EmployeeOvertimeResource;
use App\Models\EmployeeOvertime;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployeeOvertime extends CreateRecord
{
    protected static string $resource = EmployeeOvertimeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // dd($data);

        unset(
            $data['show_default_values'],
            $data['start_time_as_default'],
            $data['end_time_as_default'],
            $data['hours_as_default'],
            $data['reason_as_default'],
            $data['notes_as_default'],
        );
        $employees = $data['employees'];

        // Get the count of employees
        $employeeCount = count($employees);

        foreach ($employees as $index => $employee) {

            // Check if this is the last employee
            if ($index === $employeeCount - 1) {
                continue; // Skip the last element
            }
            EmployeeOvertime::create([
                'employee_id' => $employee['employee_id'],
                'date' => $data['date'],
                'start_time' => $employee['start_time'],
                'end_time' => $employee['end_time'],
                'hours' => $employee['hours'],
                // 'reason' => $employee['reason'],
                'notes' => $employee['notes'],
                'branch_id' => $data['branch_id'],
                'created_by' => auth()->user()->id,

            ]);
        }
        $data['employee_id'] = $employee['employee_id'];
        $data['date'] = $data['date'];
        $data['start_time'] = $employee['start_time'];
        $data['end_time'] = $employee['end_time'];
        $data['hours'] = $employee['hours'];
        // $data['reason'] = $employee['reason'];
        $data['notes'] = $employee['notes'];
        $data['branch_id'] = $data['branch_id'];
        $data['created_by'] = auth()->user()->id;
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
