<?php

namespace App\Services\HR\Applications;

use App\Models\Employee;
use App\Models\EmployeeApplicationV2;

class EmployeeApplicationService
{
    public function createApplication(array $data)
    {
        // 1) جلب الموظف
        $employee = Employee::findOrFail($data['employee_id']);

        // 2) تعيين branch_id تلقائياً
        if (!isset($data['branch_id']) && $employee->branch) {
            $data['branch_id'] = $employee->branch->id;
        }

        // 3) تعيين application_type_name تلقائياً
        if (!isset($data['application_type_name'])) {
            $data['application_type_name'] =
                EmployeeApplicationV2::APPLICATION_TYPES[$data['application_type_id']] ?? 'Unknown';
        }

        // 4) تعيين created_by والمبدئية
        $data['created_by'] = $data['created_by'] ?? auth()->id();
        $data['status']     = $data['status'] ?? EmployeeApplicationV2::STATUS_PENDING;

        // 5) إنشاء السجل الأساسي
        $record = EmployeeApplicationV2::create($data);

        // 6) إنشاء الـ relations حسب نوع الطلب
        switch ($record->application_type_id) {
            case EmployeeApplicationV2::APPLICATION_TYPE_ATTENDANCE_FINGERPRINT_REQUEST:
                $details = $data['missed_checkin_request'] ?? null;
                if ($details) {
                    $record->missedCheckinRequest()->create([
                        'application_id'        => $record->id,
                        'employee_id'           => $record->employee_id,
                        'application_type_id'   => EmployeeApplicationV2::APPLICATION_TYPE_ATTENDANCE_FINGERPRINT_REQUEST,
                        'application_type_name' => EmployeeApplicationV2::APPLICATION_TYPE_NAMES[EmployeeApplicationV2::APPLICATION_TYPE_ATTENDANCE_FINGERPRINT_REQUEST],
                        'date'                  => $details['date'],
                        'time'                  => $details['time'],
                        'reason'                => $notes['notes'] ?? null,
                    ]);
                }
                break;

            case EmployeeApplicationV2::APPLICATION_TYPE_DEPARTURE_FINGERPRINT_REQUEST:
                $details = $data['missed_checkout_request'] ?? null;
                if ($details) {
                    $record->missedCheckoutRequest()->create([
                        'application_id'        => $record->id,
                        'employee_id'           => $record->employee_id,
                        'application_type_id'   => EmployeeApplicationV2::APPLICATION_TYPE_DEPARTURE_FINGERPRINT_REQUEST,
                        'application_type_name' => EmployeeApplicationV2::APPLICATION_TYPE_NAMES[EmployeeApplicationV2::APPLICATION_TYPE_DEPARTURE_FINGERPRINT_REQUEST],
                        'date'                  => $details['date'],
                        'time'                  => $details['time'],
                        'reason'                => $data['notes'] ?? null,
                    ]);
                }
                break;

            case EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST:
                $details = $data['leave_request'] ?? null;
                if ($details) {
                    $record->leaveRequest()->create([
                        'application_id'        => $record->id,
                        'employee_id'           => $record->employee_id,
                        'application_type_id'   => EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST,
                        'application_type_name' => EmployeeApplicationV2::APPLICATION_TYPE_NAMES[EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST],
                        'start_date'            => $details['detail_from_date'],
                        'end_date'              => $details['detail_to_date'],
                        'days_count'            => $details['detail_days_count'] ?? null,
                        'leave_type'            => $details['detail_leave_type_id'] ?? null,
                        'year'  => isset($details['detail_from_date'])
                            ? \Carbon\Carbon::parse($details['detail_from_date'])->year
                            : null,

                        'month' => isset($details['detail_from_date'])
                            ? \Carbon\Carbon::parse($details['detail_from_date'])->month
                            : null,
                        'reason' => $data['notes'],
                    ]);
                }
                break;

            case EmployeeApplicationV2::APPLICATION_TYPE_ADVANCE_REQUEST:
                $details = $data['advance_request'] ?? null;
                if ($details) {
                    $record->advanceRequest()->create([
                        'application_id'               => $record->id,
                        'employee_id'                  => $record->employee_id,
                        'application_type_id'          => EmployeeApplicationV2::APPLICATION_TYPE_ADVANCE_REQUEST,
                        'application_type_name'        => EmployeeApplicationV2::APPLICATION_TYPE_NAMES[EmployeeApplicationV2::APPLICATION_TYPE_ADVANCE_REQUEST],
                        'advance_amount'               => $details['detail_advance_amount'],
                        'monthly_deduction_amount'     => $details['detail_monthly_deduction_amount'],
                        'deduction_starts_from'        => $details['detail_deduction_starts_from'],
                        'deduction_ends_at'            => $details['detail_deduction_ends_at'],
                        'date' => $data['application_date'],
                        'number_of_months_of_deduction' => $details['detail_number_of_months_of_deduction'] ?? null,
                        'reason' => $data['notes'],
                    ]);
                }
                break;
        }

        return $record;
    }

    public function updateApplication(int $id, array $data)
    {
        $record = EmployeeApplicationV2::findOrFail($id);
        $record->update($data);
        return $record;
    }

    public function deleteApplication(int $id)
    {
        $record = EmployeeApplicationV2::findOrFail($id);
        $record->delete();
    }

    public function approveApplication(int $id, int $userId)
    {
        $record = EmployeeApplicationV2::findOrFail($id);

        $record->update([
            'status'      => EmployeeApplicationV2::STATUS_APPROVED,
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);

        return $record;
    }

    public function rejectApplication(int $id, int $userId, string $reason)
    {
        $record = EmployeeApplicationV2::findOrFail($id);

        $record->update([
            'status'          => EmployeeApplicationV2::STATUS_REJECTED,
            'rejected_by'     => $userId,
            'rejected_at'     => now(),
            'rejected_reason' => $reason,
        ]);

        return $record;
    }
}
