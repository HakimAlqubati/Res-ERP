<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\EmployeePaymentMethod;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;

class EmployeeEwalletImport implements ToModel, WithHeadingRow, SkipsEmptyRows, SkipsOnError
{
    use SkipsErrors;

    private int $updatedCount = 0;
    private int $skippedCount = 0;
    private array $importErrors = [];

    private ?int $ewalletPaymentMethodId = null;

    public function __construct()
    {
        // Resolve the ewallet payment method ID once
        $ewalletMethod = EmployeePaymentMethod::where('code', EmployeePaymentMethod::CODE_EWALLET)->first();
        $this->ewalletPaymentMethodId = $ewalletMethod?->id;
    }

    public function model(array $row)
    {
        $staffNo = $row['staf_no'] ?? null;
        $ewalletNo = $row['ewallet_no'] ?? null;
        $rewardName = $row['reward_name'] ?? null;

        if (empty($staffNo)) {
            $this->importErrors[] = "Row skipped: staf_no is empty.";
            $this->skippedCount++;
            return null;
        }

        if (empty($ewalletNo)) {
            $this->importErrors[] = "Row skipped: ewallet_no is empty for staf_no {$staffNo}.";
            $this->skippedCount++;
            return null;
        }

        // Find the employee by employee_no (staf_no)
        $employee = Employee::where('employee_no', $staffNo)->first();

        if (!$employee) {
            $this->importErrors[] = "Employee not found with employee_no: {$staffNo} (Name: {$rewardName}).";
            $this->skippedCount++;
            return null;
        }

        // Update payment method and details
        $employee->update([
            'payment_method_id' => $this->ewalletPaymentMethodId,
            'payment_details' => [
                'account_name'   => 'TnG',
                'account_number' => (string) $ewalletNo,
                'full_name'      => $rewardName,
                'note'           => 'VIA TnG DIRECT SALARY SERVICE',
            ],
        ]);

        $this->updatedCount++;

        return null; // We are updating, not creating
    }

    public function headingRow(): int
    {
        return 1;
    }

    public function getUpdatedCount(): int
    {
        return $this->updatedCount;
    }

    public function getSkippedCount(): int
    {
        return $this->skippedCount;
    }

    public function getImportErrors(): array
    {
        return $this->importErrors;
    }

    public function hasErrors(): bool
    {
        return !empty($this->importErrors);
    }
}
