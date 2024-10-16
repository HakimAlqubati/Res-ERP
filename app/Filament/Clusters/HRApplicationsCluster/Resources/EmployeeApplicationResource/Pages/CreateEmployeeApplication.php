<?php

namespace App\Filament\Clusters\HRApplicationsCluster\Resources\EmployeeApplicationResource\Pages;

use App\Filament\Clusters\HRApplicationsCluster\Resources\EmployeeApplicationResource;
use App\Models\Employee;
use App\Models\EmployeeApplication;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;

class CreateEmployeeApplication extends CreateRecord
{
    protected ?bool $hasDatabaseTransactions = true;
    protected static string $resource = EmployeeApplicationResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (!isStuff()) {
            $employee = Employee::find($data['employee_id']);
            if ($employee->branch()->exists()) {
                $data['branch_id'] = $employee->branch->id;
            }
        }

        if (isStuff()) {
            $data['employee_id'] = auth()->user()->employee->id;
            $data['branch_id'] = auth()->user()->branch_id;
        }
        $applicationType = EmployeeApplication::APPLICATION_TYPES[$data['application_type']];

        $data['application_type_id'] = $data['application_type'];
        $data['application_type_name'] = $applicationType;
        $data['created_by'] = auth()->user()->id;
        $data['status'] = EmployeeApplication::STATUS_PENDING;
        $data['details'] = json_encode(EmployeeApplicationResource::getDetailsKeysAndValues($data));
        
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

   
 

    
}
