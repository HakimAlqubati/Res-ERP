<?php

namespace App\Filament\Clusters\HRApplicationsCluster\Resources\LeaveBalanceResource\Pages;

use App\Filament\Clusters\HRApplicationsCluster\Resources\LeaveBalanceResource;
use App\Models\ApplicationTransaction;
use App\Models\LeaveBalance;
use Carbon\Carbon;
use Filament\Resources\Pages\CreateRecord;

class CreateLeaveBalance extends CreateRecord
{
    protected static string $resource = LeaveBalanceResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $employees = $data['employees'];

        // Get the count of employees
        $employeeCount = count($employees);

        if($employeeCount>1){
            foreach ($employees as $index => $employee) {

                // Check if this is the last employee
                if ($index === $employeeCount - 1) {
                    continue; // Skip the last element
                }
                // Create the LeaveBalance record
                $leaveBalance = LeaveBalance::create([
                    'branch_id' => $data['branch_id'],
                    'employee_id' => $employee['employee_id'],
                    'leave_type_id' => $data['leave_type_id'],
                    'year' => $data['year'],
                    'balance' => $employee['balance'],
                    'created_by' => auth()->user()->id,
                ]);
    
                // Create the associated ApplicationTransaction record
                static::createTransaction($leaveBalance,$employee,$data);
    
            }
        }
      
        $data['branch_id'] = $data['branch_id'];
        $data['employee_id'] = $employee['employee_id'];
        $data['leave_type_id'] = $data['leave_type_id'];
        $data['year'] = $data['year'];
        $data['balance'] = $employee['balance'];
        $data['created_by'] = auth()->user()->id;
        static::createTransaction($leaveBalance,$employee,$data);
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    private static function createTransaction($leaveBalance,$employee,$data){
       return  ApplicationTransaction::create([
            'application_id' => $leaveBalance->id,
            'transaction_type_id' => 5, 
            'transaction_type_name' => ApplicationTransaction::APPLICATION_TYPES[5],
            'transaction_description' => 'Opening leave balance created for employee ID ' . $employee['employee_id'],
            'submitted_on' => Carbon::now(),
            'remaining' => $employee['balance'],
            'created_by' => auth()->user()->id,
            'employee_id' => $employee['employee_id'],
            'is_canceled' => false,
            'branch_id' => $data['branch_id'],
            'details' => json_encode(['branch_id' => $data['branch_id']]),
        ]);
    }
}
