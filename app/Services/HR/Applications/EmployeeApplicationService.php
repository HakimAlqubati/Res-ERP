<?php

namespace App\Services\HR\Applications;

use App\Models\Employee;
use App\Models\EmployeeApplicationV2;
use App\Rules\HR\Applications\AdvanceRequestConsistencyRule;
use App\Exceptions\HR\LeaveApprovalException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Format;

class EmployeeApplicationService
{
    // =========================================================================
    //  Public API
    // =========================================================================

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

        // 5) Validate advance-request fields before persisting anything
        if (($data['application_type_id'] ?? null) == EmployeeApplicationV2::APPLICATION_TYPE_ADVANCE_REQUEST) {
            $this->validateAdvanceRequest($data['advance_request'] ?? []);
        }

        // 6) إنشاء السجل الأساسي
        $record = EmployeeApplicationV2::create($data);

        // 7) إنشاء الـ relations حسب نوع الطلب
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

        // 8) Handle images
        if (isset($data['images'])) {
            $images = is_array($data['images']) ? $data['images'] : [$data['images']];
            foreach ($images as $image) {
                if ($image instanceof \Illuminate\Http\UploadedFile) {
                    $this->compressAndAddImage($record, $image);
                }
            }
        }

        // 9) Handle files
        if (isset($data['files'])) {
            $files = is_array($data['files']) ? $data['files'] : [$data['files']];
            foreach ($files as $file) {
                if ($file instanceof \Illuminate\Http\UploadedFile) {
                    $record->addMedia($file)->toMediaCollection('files');
                }
            }
        }

        return $record;
    }

    // =========================================================================
    //  Private Helpers
    // =========================================================================

    /**
     * Validate that the advance-request detail fields are internally consistent.
     *
     * Rules mirror the Filament advanceRequestForm logic exactly:
     *   - advance_amount > 0
     *   - monthly_deduction_amount > 0 and <= advance_amount
     *   - number_of_months_of_deduction == ceil(advance_amount / monthly_deduction_amount)
     *   - deduction_ends_at == startOfMonth(deduction_starts_from)
     *                           + (number_of_months - 1) months → endOfMonth
     *
     * @throws ValidationException
     */
    /**
     * ضغط الصورة وتصغيرها قبل رفعها إلى Media Library.
     * - أقصى عرض: 1600px مع الحفاظ على النسبة
     * - جودة JPEG: 80%
     */
    private function compressAndAddImage($record, \Illuminate\Http\UploadedFile $image): void
    {
        // 1. تهيئة المعالج بالطريقة الرسمية لـ V4
        $manager = ImageManager::usingDriver(Driver::class);

        $img = $manager->decode($image->getRealPath());

        // Resize
        $img->scaleDown(width: 1200);

     
        // تحويل إلى WebP (أفضل خيار)
        $encodedImage = $img->encodeUsingFormat(Format::WEBP, quality: 70);

        // 5. حفظ البيانات الثنائية في ملف مؤقت
        $tempPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('img_') . '.jpg';
        file_put_contents($tempPath, (string) $encodedImage);

        // 6. رفع الملف المؤقت إلى Media Library
        $record->addMedia($tempPath)
            ->usingName(pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME))
            ->usingFileName(uniqid('img_') . '.jpg')
            ->toMediaCollection('images');
    }

    private function validateAdvanceRequest(array $details): void
    {
        $validator = Validator::make(
            ['advance_request' => $details],
            ['advance_request' => ['required', 'array', new AdvanceRequestConsistencyRule()]]
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    public function updateApplication(int $id, array $data)
    {
        $record = EmployeeApplicationV2::findOrFail($id);
        $record->update($data);

        // Handle images
        if (isset($data['images'])) {
            $record->clearMediaCollection('images');
            $images = is_array($data['images']) ? $data['images'] : [$data['images']];
            foreach ($images as $image) {
                if ($image instanceof \Illuminate\Http\UploadedFile) {
                    $this->compressAndAddImage($record, $image);
                }
            }
        }

        // Handle files
        if (isset($data['files'])) {
            $record->clearMediaCollection('files');
            $files = is_array($data['files']) ? $data['files'] : [$data['files']];
            foreach ($files as $file) {
                if ($file instanceof \Illuminate\Http\UploadedFile) {
                    $record->addMedia($file)->toMediaCollection('files');
                }
            }
        }

        return $record;
    }

    public function deleteApplication(int $id)
    {
        $record = EmployeeApplicationV2::findOrFail($id);
        $record->delete();
    }

    public function approveApplication(int $id, int $userId)
    {
        // DB::transaction ensures that the status update AND all observer
        // side-effects (installment generation, financial transaction) are
        // atomic. Any failure rolls back everything — no orphaned state.
        return DB::transaction(function () use ($id, $userId) {
            $record = EmployeeApplicationV2::with(['missedCheckinRequest', 'missedCheckoutRequest', 'leaveRequest'])->findOrFail($id);

            switch ($record->application_type_id) {
                case EmployeeApplicationV2::APPLICATION_TYPE_ATTENDANCE_FINGERPRINT_REQUEST:
                    $details = $record->missedCheckinRequest;
                    if (!$details) {
                        throw new \Exception('Missing attendance details');
                    }

                    $result = app(\App\Modules\HR\Attendance\Services\AttendanceService::class)->handle([
                        'employee_id'                    => $record->employee_id,
                        'date_time'                      => $details->date . ' ' . $details->time,
                        'type'                           => \App\Models\Attendance::CHECKTYPE_CHECKIN,
                        'attendance_type'                => \App\Models\Attendance::ATTENDANCE_TYPE_REQUEST,
                        'skip_duplicate_timestamp_check' => true,
                    ]);

                    if (!$result->success) {
                        throw new \Exception($result->message ?? 'Failed to create attendance record');
                    }
                    break;

                case EmployeeApplicationV2::APPLICATION_TYPE_DEPARTURE_FINGERPRINT_REQUEST:
                    $details = $record->missedCheckoutRequest;
                    if (!$details) {
                        throw new \Exception('Missing departure details');
                    }

                    $result = app(\App\Modules\HR\Attendance\Services\AttendanceService::class)->handle([
                        'employee_id'                    => $record->employee_id,
                        'date_time'                      => $details->date . ' ' . $details->time,
                        'type'                           => \App\Models\Attendance::CHECKTYPE_CHECKOUT,
                        'attendance_type'                => \App\Models\Attendance::ATTENDANCE_TYPE_REQUEST,
                        'skip_duplicate_timestamp_check' => true,
                    ]);

                    if (!$result->success) {
                        throw new \Exception($result->message ?? 'Failed to create departure record');
                    }
                    break;

                case EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST:
                    app(\App\Services\HR\Applications\LeaveRequest\LeaveApprovalService::class)->process($record);
                    break;
            }

            // Updating status fires Observer::updated(). Because we are inside
            // DB::transaction(), any failure in the observer's side-effects
            // (installments, financial transaction) rolls back EVERYTHING —
            // including this status update.
            $record->update([
                'status'      => EmployeeApplicationV2::STATUS_APPROVED,
                'approved_by' => $userId,
                'approved_at' => now(),
            ]);

            return $record;
        });
    }

    /**
     * Rollback an approved application, reverting any side effects (e.g., leave balance)
     * and resetting the status to 'pending'.
     *
     * @param int $id The application ID
     * @param int $userId The ID of the user performing the rollback
     * @return EmployeeApplicationV2
     * @throws \Exception
     */
    public function undoApproveApplication(int $id, int $userId)
    {
        return DB::transaction(function () use ($id, $userId) {
            $record = EmployeeApplicationV2::with([
                'leaveRequest',
                'missedCheckinRequest',
                'missedCheckoutRequest'
            ])->findOrFail($id);

            // We only allow undo operations if the application is currently approved
            if ($record->status !== EmployeeApplicationV2::STATUS_APPROVED) {
                throw LeaveApprovalException::notApprovedStatus();
            }

            switch ($record->application_type_id) {
                case EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST:
                    app(\App\Services\HR\Applications\LeaveRequest\LeaveApprovalService::class)->undoProcess($record);
                    break;

                    // Note: Rollback for Attendance & Departure fingerprint requests can be added here if needed
            }

            // Revert state to pending and clear approval metadata
            $record->update([
                'status'      => EmployeeApplicationV2::STATUS_PENDING,
                'approved_by' => null,
                'approved_at' => null,
            ]);

            return $record;
        });
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
