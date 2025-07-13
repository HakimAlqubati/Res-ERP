<?php

namespace Database\Seeders;

use App\Models\Employee;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $all = [];
        $chunkSize = 200; // يمكنك تغيير الحجم حسب جهازك

        for ($i = 0; $i < 30000; $i++) {
            $employee = Employee::factory()->make()->toArray();
            unset($employee['id']); // تأكد من عدم إدخال id

            $all[] = $employee;

            // إذا وصلنا لعدد الدفعة، أدخل الدفعة في الجدول وافرغ المصفوفة
            if (count($all) === $chunkSize) {
                Employee::withoutEvents(function () use ($all) {
                    Employee::insert($all);
                });
                $all = [];
            }
        }

        // إذا بقي بيانات أقل من $chunkSize في النهاية أدخلها أيضاً
        if (count($all) > 0) {
            Employee::withoutEvents(function () use ($all) {
                Employee::insert($all);
            });
        }
    }
}