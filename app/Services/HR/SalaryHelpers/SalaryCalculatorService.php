<?php

namespace App\Services\HR\SalaryHelpers;

class SalaryCalculatorService
{
        public function calculate($employeeData, $salary = 300000, $workDays = 26, $dailyHours = 6)
        {
                // البيانات المستخرجة من JSON
                $present_days          = $employeeData['statistics']['present_days'];
                $partial_days          = $employeeData['statistics']['partial'];
                $absent_days           = $employeeData['statistics']['absent'];
                $total_actual_duration = $this->parseHours($employeeData['total_actual_duration_hours']); // 31:48
                $total_duration        = $this->parseHours($employeeData['total_duration_hours']);        // 24:00
                $total_overtime        = $this->parseHours($employeeData['total_approved_overtime']);     // 25:59

                // حساب سعر اليوم والساعة
                $daily_rate  = $salary / $workDays;
                $hourly_rate = $daily_rate / $dailyHours;

                // حساب خصم الغياب
                $absence_deduction = $absent_days * $daily_rate;
                // الحضور الجزئي = نصف يوم مثلاً
                $partial_deduction = $partial_days * $daily_rate * 0.5; // فرضية نصف يوم

                // حساب الإضافي
                $overtime_hours  = $total_overtime['hours'] + ($total_overtime['minutes'] / 60);
                $overtime_rate   = $hourly_rate * 1.5;
                $overtime_amount = $overtime_hours * $overtime_rate;

                // الراتب المستحق قبل الإضافي
                $base_salary = $daily_rate * ($present_days + 0.5 * $partial_days);

                // الصافي النهائي
                $net_salary = $base_salary + $overtime_amount - $absence_deduction - $partial_deduction;

                // بيانات تفصيلية للعرض أو التخزين
                return [
                        'base_salary'       => round($base_salary),
                        'absence_deduction' => round($absence_deduction),
                        'partial_deduction' => round($partial_deduction),
                        'overtime_amount'   => round($overtime_amount),
                        'net_salary'        => round($net_salary),
                        'overtime_hours'    => $overtime_hours,
                        'daily_rate'        => round($daily_rate),
                        'hourly_rate'       => round($hourly_rate, 2),
                        'details'           => $employeeData, // للعرض التفصيلي
                        'worked_days' => $present_days + (0.5 * $partial_days),
                        'gross_salary' => round($base_salary + $overtime_amount),
                        'is_negative' => $net_salary < 0,
                ];
        }

        private function parseHours($str)
        {
                // "31:48:00" to [hours => 31, minutes => 48]
                $parts = explode(':', $str);
                return [
                        'hours'   => intval($parts[0] ?? 0),
                        'minutes' => intval($parts[1] ?? 0),
                ];
        }
}
