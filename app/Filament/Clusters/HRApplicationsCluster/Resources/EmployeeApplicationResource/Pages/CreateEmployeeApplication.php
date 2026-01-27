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
use Filament\Support\Exceptions\Halt;
use Filament\Support\Facades\FilamentView;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CreateEmployeeApplication extends CreateRecord
{
    protected ?bool $hasDatabaseTransactions = true;
    protected static string $resource = EmployeeApplicationResource::class;


    public function create(bool $another = false): void
    {
        if ($this->isCreating) {
            return;
        }

        $this->isCreating = true;

        $this->authorizeAccess();

        if ($another) {
            $preserveRawState = $this->preserveFormDataWhenCreatingAnother($this->form->getRawState());
        }

        try {
            $this->beginDatabaseTransaction();

            $this->callHook('beforeValidate');

            $data = $this->form->getState();

            $this->callHook('afterValidate');

            $data = $this->mutateFormDataBeforeCreate($data);

            $this->callHook('beforeCreate');

            $this->record = $this->handleRecordCreation($data);

            $this->form->model($this->getRecord())->saveRelationships();

            $this->callHook('afterCreate');

            $this->commitDatabaseTransaction();
        } catch (Halt $exception) {
            $exception->shouldRollbackDatabaseTransaction() ?
                $this->rollBackDatabaseTransaction() :
                $this->commitDatabaseTransaction();

            $this->isCreating = false;

            return;
        } catch (ValidationException $exception) {
            $this->rollBackDatabaseTransaction();

            $this->isCreating = false;

            throw $exception;
        } catch (\Throwable $exception) {
            $this->rollBackDatabaseTransaction();

            $this->isCreating = false;

            Notification::make()
                ->title(__('lang.error'))
                ->body($exception->getMessage())
                ->danger()
                ->send();

            return;
        }

        $this->rememberData();

        $this->getCreatedNotification()?->send();

        if ($another) {
            // Ensure that the form record is anonymized so that relationships aren't loaded.
            $this->form->model($this->getRecord()::class);
            $this->record = null;

            $this->fillForm();

            $this->form->rawState([
                ...$this->form->getRawState(),
                ...$preserveRawState,
            ]);

            $this->isCreating = false;

            return;
        }

        $redirectUrl = $this->getRedirectUrl();

        $this->redirect($redirectUrl, navigate: FilamentView::hasSpaMode($redirectUrl));
    }


    protected function mutateFormDataBeforeCreate(array $data): array
    {

        // dd($data);
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
            $attendances = $employee->attendancesByDate($this->data['missedCheckinRequest']['date'])->where('check_type', Attendance::CHECKTYPE_CHECKIN)->count();

            if ($attendances > 0) {
                // Notification::make()->body('Employee has already checked in(' . $this->data['missedCheckinRequest']['date'] . ')')->warning()->send();

                // Throw a validation exception if an application exists
                // throw ValidationException::withMessages([
                //     'application_date' => 'Employee has already checked in today.(' . $this->data['missedCheckinRequest']['date'] . ')',
                // ]);
            }
        }

        if ($data['application_type_id'] == EmployeeApplicationV2::APPLICATION_TYPE_DEPARTURE_FINGERPRINT_REQUEST) {
            $attendances = $employee->attendancesByDate($this->data['missedCheckoutRequest']['detail_date'])->where('check_type', Attendance::CHECKTYPE_CHECKOUT)->get();

            // if (count($attendances) == 0) {
            //     Notification::make()->body('Employee has not checked in today.(' . $this->data['missedCheckoutRequest']['detail_date'] . ')')->warning()->send();
            //     throw ValidationException::withMessages([
            //         'application_date' => 'Employee has not checked in today.(' . $this->data['missedCheckoutRequest']['detail_date'] . ')',
            //     ]);
            // }

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
        $data['status'] = EmployeeApplicationV2::STATUS_PENDING;

        // $data['details'] = [];

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index') . EmployeeApplicationV2::APPLICATION_TYPE_FILTERS[$this->record->application_type_id];
    }
}
