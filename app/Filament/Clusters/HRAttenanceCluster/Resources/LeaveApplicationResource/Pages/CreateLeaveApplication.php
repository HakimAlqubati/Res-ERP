<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\LeaveApplicationResource\Pages;

use App\Filament\Clusters\HRAttenanceCluster\Resources\LeaveApplicationResource;
use App\Models\Employee;
use App\Models\LeaveApplication;
use Filament\Resources\Pages\CreateRecord;

class CreateLeaveApplication extends CreateRecord
{
    protected static string $resource = LeaveApplicationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if ($data['from_date'] && $data['to_date']) {
            $daysDiff = now()->parse($data['from_date'])->diffInDays(now()->parse($data['to_date'])) + 1;
            $data['days_count'] = $daysDiff;
        } else {
            $data['days_count'] = 0;
        }

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
        $data['created_by'] = auth()->user()->id;
        $data['status'] = LeaveApplication::STATUS_PENDING;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

}
