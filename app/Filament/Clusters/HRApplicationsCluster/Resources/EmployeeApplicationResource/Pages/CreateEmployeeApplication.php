<?php

namespace App\Filament\Clusters\HRApplicationsCluster\Resources\EmployeeApplicationResource\Pages;

use App\Filament\Clusters\HRApplicationsCluster\Resources\EmployeeApplicationResource;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeApplication;
use App\Models\EmployeeApplicationV2;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CreateEmployeeApplication extends CreateRecord
{
    protected ?bool $hasDatabaseTransactions = true;
    protected static string $resource = EmployeeApplicationResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $last = EmployeeApplicationV2::orderBy('id', 'desc')->first();
        $data['id'] = $last->id + 1;
        if (!isStuff() && !isFinanceManager()) {
            $employee = Employee::find($data['employee_id']);
            if ($employee->branch()->exists()) {
                $data['branch_id'] = $employee->branch->id;
            }
        }

        if (isStuff() || isFinanceManager()) {
            $data['employee_id'] = auth()->user()->employee->id;
            $data['branch_id'] = auth()->user()->branch_id;
            $employee = Employee::find($data['employee_id']);
            if ($employee->branch()->exists()) {
                $data['branch_id'] = $employee->branch->id;
            }
        }

        $applicationType = EmployeeApplicationV2::APPLICATION_TYPES[$data['application_type_id']];

        $data['application_type_id'] = $data['application_type_id'];
        $data['application_type_name'] = $applicationType;
        $year = Carbon::parse($data['application_date'])->year;
        $month = Carbon::parse($data['application_date'])->month;

        // Check if an application already exists for the same employee and date
        $existingApplicationWithDate = EmployeeApplicationV2::where('employee_id', $data['employee_id'])
            ->where('application_date', $data['application_date'])
            ->whereYear('application_date', $year)
            ->whereMonth('application_date', $month)
            ->where('application_type_id', $data['application_type_id'])
            ->first();

        if ($existingApplicationWithDate) {
            // Notification::make()->body('An application already exists for this employee on the selected date.')->warning()->send();
            // Log::warning('An application already exists for this employee on the selected date.');
            // // Throw a validation exception if an application exists
            // throw ValidationException::withMessages([
            //     'application_date' => 'An application already exists for this employee on the selected date.',
            // ]);
        }

        $existingApplications = EmployeeApplicationV2::where('employee_id', $data['employee_id'])
            ->whereYear('application_date', $year)
            ->whereMonth('application_date', $month)
            ->where('application_type_id', $data['application_type_id'])
            ->get();

        if ($existingApplications && $data['application_type_id'] == EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST) {
            foreach ($existingApplications as $existingApplication) {
                $existedStartDate = $existingApplication->leaveRequest->start_date ?? null;
                $existedEndDate = $existingApplication?->leaveRequest?->end_date;
                $requestedStartDate = $this->data['leaveRequest']['detail_from_date'];
                $requestedEndDate = $this->data['leaveRequest']['detail_to_date'];
                // Check for nulls first
                if ($existedStartDate && $existedEndDate && $requestedStartDate && $requestedEndDate) {
                    // Convert strings to Carbon instances to easily compare dates
                    $existedStartDate = Carbon::parse($existedStartDate);
                    $existedEndDate = Carbon::parse($existedEndDate);
                    $requestedStartDate = Carbon::parse($requestedStartDate);
                    $requestedEndDate = Carbon::parse($requestedEndDate);

                    // Check if the existing dates overlap with the requested dates
                    $isOverlap = $existedStartDate->lte($requestedEndDate) && $existedEndDate->gte($requestedStartDate);

                    if ($isOverlap) {
                        Notification::make()->body('An application already exists for this employee on the selected date.')->warning()->send();
                        Log::warning('An application already exists for this employee on the selected date.');
                        // Throw a validation exception if an application exists
                        throw ValidationException::withMessages([
                            'application_date' => 'An application already exists for this employee on the selected date.',
                        ]);
                    }
                }
            }
        }

        if ($data['application_type_id'] == EmployeeApplicationV2::APPLICATION_TYPE_ATTENDANCE_FINGERPRINT_REQUEST) {
            $attendances = $employee->attendancesByDate($this->data['missedCheckinRequest']['detail_date'])->count();

            if ($attendances > 0) {
                Notification::make()->body('Employee has already checked in(' . $this->data['missedCheckinRequest']['detail_date'] . ')')->warning()->send();

                // Throw a validation exception if an application exists
                throw ValidationException::withMessages([
                    'application_date' => 'Employee has already checked in today.(' . $this->data['missedCheckinRequest']['detail_date'] . ')',
                ]);
            }
        }

        if ($data['application_type_id'] == EmployeeApplicationV2::APPLICATION_TYPE_DEPARTURE_FINGERPRINT_REQUEST) {
            $attendances = $employee->attendancesByDate($this->data['missedCheckoutRequest']['detail_date'])->get();

            if (count($attendances) == 0) {
                Notification::make()->body('Employee has not checked in today.(' . $this->data['missedCheckoutRequest']['detail_date'] . ')')->warning()->send();
                throw ValidationException::withMessages([
                    'application_date' => 'Employee has not checked in today.(' . $this->data['missedCheckoutRequest']['detail_date'] . ')',
                ]);
            }

            if (count($attendances) > 0) {
                $lastAttendance = $attendances->last();
                if ($lastAttendance && $lastAttendance->check_type === Attendance::CHECKTYPE_CHECKOUT) {
                    Notification::make()->body('Employee has already checked out today.(' . $this->data['missedCheckoutRequest']['detail_date'] . ')')->warning()->send();
                    throw ValidationException::withMessages([
                        'application_date' => 'Employee has already checked out today.(' . $this->data['missedCheckoutRequest']['detail_date'] . ')',
                    ]);
                }
            }
        }

        $data['application_type_id'] = $data['application_type_id'];
        $data['application_type_name'] = $applicationType;
        $data['created_by'] = auth()->user()->id;
        $data['status'] = EmployeeApplication::STATUS_PENDING;

        // $data['details'] = [];

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index') . EmployeeApplicationV2::APPLICATION_TYPE_FILTERS[$this->record->application_type_id];
    }
}
