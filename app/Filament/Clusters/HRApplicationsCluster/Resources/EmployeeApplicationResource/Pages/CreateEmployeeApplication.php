<?php

namespace App\Filament\Clusters\HRApplicationsCluster\Resources\EmployeeApplicationResource\Pages;

use App\Filament\Clusters\HRApplicationsCluster\Resources\EmployeeApplicationResource;
use App\Models\Employee;
use App\Models\EmployeeApplication;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

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
        // Log::warning('An application already exists for this employee on the selected date.');
        
        $data['application_type_id'] = $data['application_type'];
        $data['application_type_name'] = $applicationType;
        // dd($data);
        // Check if an application already exists for the same employee and date
        $existingApplication = EmployeeApplication::where('employee_id', $data['employee_id'])
            ->where('application_date', $data['application_date'])
            ->where('application_type_id', $data['application_type_id'])
            ->where('status',EmployeeApplication::STATUS_APPROVED)
            ->first();

            if ($existingApplication) {
                Notification::make()->body('An application already exists for this employee on the selected date.')->warning()->send();
                Log::warning('An application already exists for this employee on the selected date.');
                // Throw a validation exception if an application exists
                throw ValidationException::withMessages([
                    'application_date' => 'An application already exists for this employee on the selected date.',
                ]);
            }
            

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
