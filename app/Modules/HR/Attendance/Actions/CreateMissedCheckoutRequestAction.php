<?php

namespace App\Modules\HR\Attendance\Actions;

use App\Models\EmployeeApplicationV2;
use App\Models\MissedCheckOutRequest;
use App\Modules\HR\Attendance\DTOs\AttendanceContextDTO;

/**
 * Action: Create an Auto-Generated Missed Checkout Request
 *
 * Triggered when an employee attempts a check-out but no open check-in record
 * exists for that shift. Instead of rejecting the request outright, the system
 * automatically creates a pending application to be reviewed by HR.
 *
 * Responsibilities:
 *  - Create a new EmployeeApplicationV2 of type DEPARTURE_FINGERPRINT_REQUEST.
 *  - Create the associated MissedCheckOutRequest detail record.
 *
 * This class is intentionally kept free of validation logic; all guard
 * conditions must be evaluated by the caller (AttendanceHandler) before
 * invoking execute().
 */
class CreateMissedCheckoutRequestAction
{
    /**
     * Execute the action and persist both the application and its detail record.
     *
     * @param  AttendanceContextDTO  $context  The resolved attendance context.
     * @return void
     */
    public function execute(AttendanceContextDTO $context): void
    {
        $application = $this->createApplication($context);
        $this->createMissedCheckoutDetail($application, $context);
    }

    // -------------------------------------------------------------------------
    // Private Helpers
    // -------------------------------------------------------------------------

    /**
     * Create the parent EmployeeApplicationV2 record.
     *
     * @param  AttendanceContextDTO  $context
     * @return EmployeeApplicationV2
     */
    private function createApplication(AttendanceContextDTO $context): EmployeeApplicationV2
    {
        return EmployeeApplicationV2::create([
            'employee_id'          => $context->employee->id,
            'branch_id'            => $context->employee->branch_id,
            'application_date'     => $context->requestTime->toDateString(),
            'status'               => EmployeeApplicationV2::STATUS_PENDING,
            'application_type_id'  => EmployeeApplicationV2::APPLICATION_TYPE_DEPARTURE_FINGERPRINT_REQUEST,
            'application_type_name'=> EmployeeApplicationV2::APPLICATION_TYPE_NAMES[
                EmployeeApplicationV2::APPLICATION_TYPE_DEPARTURE_FINGERPRINT_REQUEST
            ],
            'created_by'           => auth()->id() ?? $context->employee->user_id ?? 0,
            'is_auto_generated'    => true,
        ]);
    }

    /**
     * Create the MissedCheckOutRequest detail linked to the parent application.
     *
     * @param  EmployeeApplicationV2  $application
     * @param  AttendanceContextDTO   $context
     * @return void
     */
    private function createMissedCheckoutDetail(EmployeeApplicationV2 $application, AttendanceContextDTO $context): void
    {
        MissedCheckOutRequest::create([
            'application_id'       => $application->id,
            'application_type_id'  => $application->application_type_id,
            'application_type_name'=> $application->application_type_name,
            'employee_id'          => $context->employee->id,
            'date'                 => $context->shiftDate ?? $context->requestTime->toDateString(),
            'time'                 => $context->requestTime->format('H:i'),
            'reason'               => __('lang.auto_generated_reason_missing_checkin', [
                'default' => 'Auto-generated: check-out recorded without a matching check-in fingerprint.',
            ]),
            'is_auto_generated'    => true,
        ]);
    }
}
