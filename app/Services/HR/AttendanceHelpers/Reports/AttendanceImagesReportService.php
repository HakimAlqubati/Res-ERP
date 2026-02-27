<?php

namespace App\Services\HR\AttendanceHelpers\Reports;

use App\Models\AttendanceImagesUploaded;
use App\Models\Attendance;
use Illuminate\Http\Request;

class AttendanceImagesReportService
{
    /**
     * Determines whether to return only the first CheckIn and last CheckOut.
     * Set this property to true to enable this filtering behavior.
     *
     * @var bool
     */
    public bool $returnOnlyFirstAndLast = false;

    /**
     * Determines whether to include 'request' type attendances.
     * Set this property to true to enable this behavior.
     *
     * @var bool
     */
    public bool $includeRequests = false;

    /**
     * Fetch and format attendance images based on the applied filters.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getImagesReport(Request $request)
    {
        $query = Attendance::where('accepted', 1)
            ->when(!$this->includeRequests, function ($q) {
                // Return only actual webcam/photo check-ins/check-outs when requests are NOT included
                $q->where('source_type', AttendanceImagesUploaded::class);
            })
            ->with(['employee:id,name,branch_id', 'period:id,name', 'source']);

        // Filter by employee
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->input('employee_id'));
        }

        // Filter by date
        if ($request->filled('from_date')) {
            $query->whereDate('check_date', '>=', $request->input('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->whereDate('check_date', '<=', $request->input('to_date'));
        }

        // Filter by branch
        if ($request->filled('branch_id')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('branch_id', $request->input('branch_id'));
            });
        }

        $perPage = $request->input('per_page', 20);

        $sortOrder = $request->input('sort_order', 'asc');
        $sortOrder = strtolower($sortOrder) === 'desc' ? 'desc' : 'asc';

        // Sorting by check_date, employee_id
        $attendances = $query->orderBy('check_date', $sortOrder)
            ->orderBy('employee_id')
            ->paginate($perPage);

        // Map and filter logic
        $mappedImages = $attendances->getCollection()->map(function ($attendance) {
            $source = $attendance->source;
            $isImageUpload = $attendance->source_type === AttendanceImagesUploaded::class;

            // Filter Logic: If enabled, only return First CheckIn and Last CheckOut per (employee, date, period)
            if ($this->returnOnlyFirstAndLast) {
                $keep = false;

                if ($attendance->check_type == Attendance::CHECKTYPE_CHECKIN) {
                    $earlierExists = Attendance::where('employee_id', $attendance->employee_id)
                        ->where('check_date', $attendance->check_date)
                        ->where('period_id', $attendance->period_id)
                        ->where('check_type', Attendance::CHECKTYPE_CHECKIN)
                        ->where('accepted', 1)
                        ->where('id', '<', $attendance->id)
                        ->exists();

                    if (!$earlierExists) {
                        $keep = true;
                    }
                } elseif ($attendance->check_type == Attendance::CHECKTYPE_CHECKOUT) {
                    $laterExists = Attendance::where('employee_id', $attendance->employee_id)
                        ->where('check_date', $attendance->check_date)
                        ->where('period_id', $attendance->period_id)
                        ->where('check_type', Attendance::CHECKTYPE_CHECKOUT)
                        ->where('accepted', 1)
                        ->where('id', '>', $attendance->id)
                        ->exists();

                    if (!$laterExists) {
                        $keep = true;
                    }
                }

                if (!$keep) {
                    return null;
                }
            }

            $attendanceStatus = $attendance->status;

            if ($attendance->check_type == Attendance::CHECKTYPE_CHECKOUT) {
                if ($attendanceStatus === Attendance::STATUS_EARLY_DEPARTURE) {
                    $earlyMinutes = (int) ($attendance->early_departure_minutes ?? 0);
                    $graceMinutes = (int) settingWithDefault('early_depature_deduction_minutes', 0);

                    if ($earlyMinutes <= $graceMinutes) {
                        $attendanceStatus = Attendance::STATUS_ON_TIME;
                    }
                } elseif ($attendanceStatus === Attendance::STATUS_LATE_DEPARTURE) {
                    $lateMinutes = (int) ($attendance->late_departure_minutes ?? 0);
                    $graceMinutes = (int) settingWithDefault('early_depature_deduction_minutes', 0);

                    if ($lateMinutes <= $graceMinutes) {
                        $attendanceStatus = Attendance::STATUS_ON_TIME;
                    }
                }
            }

            $isImageUpload = $source && str_contains($attendance->source_type, 'AttendanceImagesUploaded');
            $defaultImage = 'https://ui-avatars.com/api/?name=Missed+Checkout&color=7F9CF5&background=EBF4FF';
            $imgUrl = $isImageUpload && !empty($source->img_url) ? $source->full_image_url : $defaultImage;
            $datetime = $isImageUpload && !empty($source->datetime) ? $source->datetime : $attendance->check_date . ' ' . $attendance->check_time;
            $imageId = $isImageUpload && $source ? $source->id : $attendance->id;

            return [
                'id'             => $imageId,
                'img_url'        => $imgUrl,
                'employee_id'    => $attendance->employee_id,
                'employee_name'  => $attendance->employee?->name ?? 'Unknown',
                'datetime'       => $datetime,
                'check_date'     => $attendance->check_date,
                'real_check_date'  => $attendance->real_check_date,
                'attendance'     => [
                    'id'               => $attendance->id,
                    'check_type'       => $attendance->check_type,
                    'attendance_type'  => $attendance->attendance_type,
                    'check_type_label' => Attendance::getCheckTypes()[$attendance->check_type] ?? $attendance->check_type,
                    'status'           => $attendanceStatus,
                    'status_label'     => Attendance::getStatusLabel($attendanceStatus),
                    'status_color'     => Attendance::getStatusColor($attendanceStatus),
                    'status_hex'       => Attendance::getStatusHex($attendanceStatus),
                    'check_date'       => $attendance->check_date,
                    'real_check_date'  => $attendance->real_check_date,
                    'check_time'       => $attendance->check_time,
                    'period_id'        => $attendance->period_id,
                    'period_name'      => $attendance->period ? $attendance->period->name : null,
                ],
            ];
        })->filter()->values();

        // Group by check_date -> employee_id
        $groupedImages = $mappedImages->groupBy('check_date')->map(function ($dateGroup, $date) {
            $employees = $dateGroup->groupBy('employee_id')->map(function ($empGroup, $empId) {
                $first = $empGroup->first();
                $employeeImages = $empGroup->map(function ($item) {
                    return [
                        'id'         => $item['id'],
                        'img_url'    => $item['img_url'],
                        'datetime'   => $item['datetime'],
                        'attendance' => $item['attendance'],
                    ];
                })->values();

                return [
                    'employee_id'   => $empId,
                    'employee_name' => $first['employee_name'],
                    'images'        => $employeeImages,
                ];
            })->values();

            return [
                'check_date' => $date,
                'employees'  => $employees,
            ];
        })->values();

        $attendances->setCollection($groupedImages);

        return $attendances;
    }
}
