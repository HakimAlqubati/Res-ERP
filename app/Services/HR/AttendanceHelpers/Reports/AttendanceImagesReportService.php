<?php

namespace App\Services\HR\AttendanceHelpers\Reports;

use App\Models\AttendanceImagesUploaded;
use App\Models\Attendance;
use Illuminate\Http\Request;

class AttendanceImagesReportService
{
    /**
     * Fetch and format attendance images based on the applied filters.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getImagesReport(Request $request)
    {
        $query = AttendanceImagesUploaded::query()
            ->select('attendance_images_uploaded.*')
            ->join('hr_attendances', function ($join) {
                $join->on('hr_attendances.source_id', '=', 'attendance_images_uploaded.id')
                    ->where('hr_attendances.source_type', '=', AttendanceImagesUploaded::class)
                    ->where('hr_attendances.accepted', 1);
            })
            ->with(['employee:id,name,branch_id', 'attendances' => function ($q) {
                $q->where('accepted', 1)
                    ->select('id', 'source_type', 'source_id', 'check_type', 'status', 'check_date', 'real_check_date', 'check_time', 'employee_id', 'period_id')
                    ->with('period:id,name');
            }]);

        // Filter by employee
        if ($request->filled('employee_id')) {
            $query->where('attendance_images_uploaded.employee_id', $request->input('employee_id'));
        }

        // Filter by date
        if ($request->filled('from_date')) {
            $query->whereDate('hr_attendances.check_date', '>=', $request->input('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->whereDate('hr_attendances.check_date', '<=', $request->input('to_date'));
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
        $images = $query->orderBy('hr_attendances.check_date', $sortOrder)
            ->orderBy('attendance_images_uploaded.employee_id')
            ->paginate($perPage);

        // Map and filter logic
        $mappedImages = $images->getCollection()->map(function ($image) {
            $attendance = $image->attendances->first();

            // Filter Logic: Only return First CheckIn and Last CheckOut per (employee, date, period)
            if ($attendance) {
                $keep = false;

                if ($attendance->check_type == Attendance::CHECKTYPE_CHECKIN) {
                    // Check if there is any strictly earlier checkin
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
                    // Check if there is any strictly later checkout
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

                // If filter failed, return null to remove from list
                if (!$keep) {
                    return null;
                }
            } else {
                return null; // Ignore if no accepted attendance found
            }

            return [
                'id'             => $image->id,
                'img_url'        => $image->full_image_url,
                'employee_id'    => $image->employee_id,
                'employee_name'  => $image->employee?->name ?? 'Unknown',
                'datetime'       => $image->datetime,
                'check_date'     => $attendance->check_date,
                'real_check_date'  => $attendance->real_check_date,
                'attendance'     => [
                    'id'               => $attendance->id,
                    'check_type'       => $attendance->check_type,
                    'check_type_label' => Attendance::getCheckTypes()[$attendance->check_type] ?? $attendance->check_type,
                    'status'           => $attendance->status,
                    'status_label'     => Attendance::getStatusLabel($attendance->status),
                    'status_color'     => Attendance::getStatusColor($attendance->status),
                    'status_hex'       => Attendance::getStatusHex($attendance->status),
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

        $images->setCollection($groupedImages);

        return $images;
    }
}
