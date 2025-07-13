<?php
namespace Database\Seeders;

use App\Models\Employee;
use App\Models\EmployeePeriod;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class EmployeePeriodHistorySeeder extends Seeder
{
    public function run(): void
    {
        $periodId  = 8;
        $days      = ['sat', 'sun', 'mon', 'tue'];
        $startDate = Carbon::now()->toDateString();

        // جلب جميع الموظفين IDs
        $employees = Employee::pluck('id');

        foreach ($employees as $employeeId) {
            // إنشاء سجل EmployeePeriod للفترة إذا غير موجود
            $employeePeriod = EmployeePeriod::firstOrCreate([
                'employee_id' => $employeeId,
                'period_id'   => $periodId,
            ]);
            foreach ($days as $dayOfWeek) {
                // فحص يدوي للتكرار حسب القيد الفريد فقط
                $exists = \App\Models\EmployeePeriodDay::where('employee_period_id', $employeePeriod->id)
                    ->where('day_of_week', $dayOfWeek)
                    ->exists();

                if (! $exists) {
                    \App\Models\EmployeePeriodDay::create([
                        'employee_period_id' => $employeePeriod->id,
                        'day_of_week'        => $dayOfWeek,
                        'start_date'         => $startDate, // لو تحتاج حفظ start_date أو اتركها null لو لم تلزمك
                        'end_date'           => null,
                    ]);
                }

                $exists = \App\Models\EmployeePeriodHistory::where('employee_id', $employeeId)
                    ->where('period_id', $periodId)
                    ->where('day_of_week', $dayOfWeek)
                    ->exists();

                if (! $exists) {
                    \App\Models\EmployeePeriodHistory::create([
                        'employee_id' => $employeeId,
                        'period_id'   => $periodId,
                        'day_of_week' => $dayOfWeek,
                        'start_date'  => $startDate,
                        'end_date'    => null,
                        // أضف start_time, end_time أو غيرها لو لزم الأمر
                    ]);
                }
            }
        }
    }
}