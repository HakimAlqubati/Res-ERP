<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\SalaryTransaction;

class SalaryTransactionSeeder extends Seeder
{
    public function run(): void
    {
        // تأكد من وجود الموظف رقم 1
        $employee = Employee::firstOrCreate([
            'id' => 1,
        ], [
            'name' => 'Test Employee',
        ]);

        // احذف الحركات السابقة لهذا الموظف
        SalaryTransaction::where('employee_id', $employee->id)->delete();

        // بيانات شهري 6 و 7 لعام 2025
        $months = [
            [
                'month' => 6,
                'year'  => 2025,
                'salary_date'      => '2025-06-01',
                'allowance_date'   => '2025-06-03',
                'bonus_date'       => '2025-06-10',
                'deduction_date'   => '2025-06-14',
                'advance_date'     => '2025-06-20',
            ],
            [
                'month' => 7,
                'year'  => 2025,
                'salary_date'      => '2025-07-01',
                'allowance_date'   => '2025-07-04',
                'bonus_date'       => '2025-07-12',
                'deduction_date'   => '2025-07-18',
                'advance_date'     => '2025-07-24',
            ],
        ];

        foreach ($months as $data) {
            // راتب أساسي
            SalaryTransaction::create([
                'employee_id'    => $employee->id,
                'year'           => $data['year'],
                'month'          => $data['month'],
                'date'           => $data['salary_date'],
                'amount'         => 100000,
                'type'           => SalaryTransaction::TYPE_SALARY,
                'operation'      => SalaryTransaction::OPERATION_ADD,
                'description'    => "Basic Salary {$data['month']}/{$data['year']}",
                'status'         => SalaryTransaction::STATUS_APPROVED,
            ]);
            // بدل مواصلات
            SalaryTransaction::create([
                'employee_id'    => $employee->id,
                'year'           => $data['year'],
                'month'          => $data['month'],
                'date'           => $data['allowance_date'],
                'amount'         => 20000,
                'type'           => SalaryTransaction::TYPE_ALLOWANCE,
                'operation'      => SalaryTransaction::OPERATION_ADD,
                'description'    => "Transport Allowance {$data['month']}/{$data['year']}",
                'status'         => SalaryTransaction::STATUS_APPROVED,
            ]);
            // مكافأة
            SalaryTransaction::create([
                'employee_id'    => $employee->id,
                'year'           => $data['year'],
                'month'          => $data['month'],
                'date'           => $data['bonus_date'],
                'amount'         => 12000,
                'type'           => SalaryTransaction::TYPE_BONUS,
                'operation'      => SalaryTransaction::OPERATION_ADD,
                'description'    => "Performance Bonus {$data['month']}/{$data['year']}",
                'status'         => SalaryTransaction::STATUS_APPROVED,
            ]);
            // خصم
            SalaryTransaction::create([
                'employee_id'    => $employee->id,
                'year'           => $data['year'],
                'month'          => $data['month'],
                'date'           => $data['deduction_date'],
                'amount'         => 5000, // موجبة دائماً
                'type'           => SalaryTransaction::TYPE_DEDUCTION,
                'operation'      => SalaryTransaction::OPERATION_SUB,
                'description'    => "Penalty Deduction {$data['month']}/{$data['year']}",
                'status'         => SalaryTransaction::STATUS_APPROVED,
            ]);
            // سلفة
            SalaryTransaction::create([
                'employee_id'    => $employee->id,
                'year'           => $data['year'],
                'month'          => $data['month'],
                'date'           => $data['advance_date'],
                'amount'         => 15000, // موجبة دائماً
                'type'           => SalaryTransaction::TYPE_ADVANCE,
                'operation'      => SalaryTransaction::OPERATION_SUB,
                'description'    => "Salary Advance {$data['month']}/{$data['year']}",
                'status'         => SalaryTransaction::STATUS_APPROVED,
            ]);
        }
    }
}