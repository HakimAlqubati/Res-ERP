<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\AttendnaceResource\Pages;

use App\Filament\Clusters\HRAttenanceCluster\Resources\AttendnaceResource;
use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Filament\Resources\Pages\CreateRecord;

class CreateAttendnace extends CreateRecord
{
    protected static string $resource = AttendnaceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['day'] = \Carbon\Carbon::parse($data['check_date'])->format('l');
        $data['created_by'] = auth()->user()->id;
        $data['updated_by'] = auth()->user()->id;

        $employee = Employee::find($data['employee_id']);
        if ($employee->branch()->exists()) {
            $data['branch_id'] = $employee->branch->id;
        }
        $data2 = attendanceEmployee($employee, $data['check_time'], $data['day'], $data['check_type'],$data['check_date']);
        // dd($workTimePeriod, $data);
       $data= array_merge($data,$data2);
        // dd($data);
        return $data;
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
