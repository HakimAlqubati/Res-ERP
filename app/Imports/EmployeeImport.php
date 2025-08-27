<?php

namespace App\Imports;

use Exception;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Validators\ValidationException;

class EmployeeImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use SkipsFailures;
    private $successfulImportsCount = 0; //
    public function model(array $row)
    {
        // Parse the join_date field
        $joinDate = isset($row['join_date']) && !empty($row['join_date'])
            ? Carbon::parse($row['join_date'])->format('Y-m-d')
            : null;
        try {
            //code...
            if (!isset($row['name']) || empty($row['name'])) {
                showWarningNotifiMessage('Employee name is missing');
                return null; // Skip row
            }


            $this->successfulImportsCount++;
            return new Employee([
                'name' => $row['name'],
                'phone_number' => $row['phone_number'] ?? null,
                'job_title' => $row['job_title'] ?? null,
                'email' => $row['email'] ?? null,
                'salary' => $row['salary'] ?? 0,
                'nationality' => $row['nationality'] ?? null,
                'has_employee_pass' => $row['has_employee_pass'] ?? 0,
                'gender' => $row['gender'] ?? null,
                'branch_id' => $row['branch_id'] ?? null,
                'join_date' => $joinDate,
            ]);
        } catch (Exception $e) {
            Log::error('Error processing row: ' . json_encode($row) . ' - ' . $e->getMessage());
            return null; // Skip row with error
        }
    }
    public function headings(): array
    {
        return [
            'name',
            'phone_number',
            'job_title',
            'email',
            'salary',
            'branch_id',
            'nationality',
            'gender',
            'has_employee_pass',
            'join_date',
        ];
    }

    public function headingRow(): int
    {
        return 1;
    }

    // Getter for successful imports count
    public function getSuccessfulImportsCount(): int
    {
        return $this->successfulImportsCount;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|unique:hr_employees,name',
            'email' => 'unique:hr_employees,email',
        ];
    }
}
