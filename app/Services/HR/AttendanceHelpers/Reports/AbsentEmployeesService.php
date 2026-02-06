<?php

namespace App\Services\HR\AttendanceHelpers\Reports;

use App\Enums\HR\Attendance\AttendanceReportStatus;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AbsentEmployeesService
{
    protected EmployeesAttendanceOnDateService $attendanceService;

    public function __construct(EmployeesAttendanceOnDateService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    /**
     * الحصول على قائمة الموظفين الغائبين في يوم محدد.
     * الغياب يعني عدم وجود أي بصمة (حضور أو انصراف) وأن الحالة النهائية لليوم هي Absent.
     *
     * @param Carbon|string $date
     * @param array $filters (اختياري) فلاتر للموظفين مثل branch_id, department_id
     * @return Collection مجموعة تحتوي بيانات الموظفين الغائبين
     */
    public function getAbsentEmployees($date, array $filters = []): Collection
    { 
        $date = $date instanceof Carbon ? $date : Carbon::parse($date);

        // 1. جلب الموظفين (مع تطبيق الفلاتر إن وجدت)
        $employeesQuery = Employee::query(); // تأكد من استبعاد المستقيلين إذا لزم الأمر ->active()

        if (!empty($filters['branch_id'])) {
            $employeesQuery->where('branch_id', $filters['branch_id']);
        }

        if (!empty($filters['department_id'])) {
            $employeesQuery->where('department_id', $filters['department_id']);
        }

        // يمكنك إضافة فلاتر أخرى هنا
        $employees = $employeesQuery->get();

        // 2. استخدام الخدمة الموجودة لجلب تقرير الحضور لكل هؤلاء الموظفين
        // هذه الخطوة قد تكون ثقيلة إذا كان العدد كبيراً جداً، لذا يفضل استخدامها للنطاقات المعقولة
        $attendanceReports = $this->attendanceService->fetchAttendances($employees, $date);

        // 3. تصفية النتائج لاستخراج الغائبين فقط
        $absentEmployees = $attendanceReports->filter(function ($item) use ($date, $filters) {
            $report = $item['attendance_report'];
            $dateString = $date->format('Y-m-d');

            // التقرير يكون مفهرساً بالتاريخ، نتحقق من وجود اليوم
            if (!isset($report[$dateString])) {
                return false;
            }

            $dayData = $report[$dateString];

            // التحقق من أن حالة اليوم هي "غياب" (Absent)
            // هذا يعتمد على المنطق الموجود في AttendanceFetcher الذي يحدد الحالة
            $isAbsent = isset($dayData['day_status']) &&
                $dayData['day_status'] === AttendanceReportStatus::Absent->value;

            if (!$isAbsent) {
                return false;
            }

             // إذا تم تمرير وقت محدد (current_time)، نتأكد أن الموظف لديه وردية بدأت بالفعل قبل هذا الوقت.
            // إذا كانت جميع وردياته في المستقبل بالنسبة لهذا الوقت، فلا نعتبره غائباً (حتى الآن).
            if (!empty($filters['current_time'])) {
                $currentTime = $filters['current_time']; // Format H:i potentially
                $passedShiftStart = false;

                $periods = $dayData['periods'] ?? [];

                // AttendanceFetcher يعيد الفترات كمجموعة (Collection) أو مصفوفة
                foreach ($periods as $period) {
                    $startTime = $period['start_time'] ?? null;
                    if (!$startTime) continue;

                    // dd($currentTime, $startTime);
                    // تحويل ومقارنة وقت البداية. نفترض تنسيق H:i:s أو H:i
                    if (strtotime($currentTime) >= strtotime($startTime)) {
                        $passedShiftStart = true;
                        break;
                    }
                }

                if (!$passedShiftStart) {
                    return false;
                }
            }

            return true;
        });

        return $absentEmployees->values(); // إعادة ترتيب المفاتيح
    }
}
