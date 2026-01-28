<?php

namespace App\Imports;

use Exception;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use App\Models\Employee;
use App\Models\Branch;
use App\Models\WorkPeriod;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Validators\ValidationException;

class WorkPeriodImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use SkipsFailures;
    private $successfulImportsCount = 0; //
    public function model(array $row)
    {
        try {
            //code...
            if (!isset($row['shift_name']) || empty($row['shift_name'])) {
                showWarningNotifiMessage('Shift name is missing');
                return null; // Skip row
            }

            // Parse and validate time fields
            $startAt = $this->convertExcelTime($row['start_time']);
            $endAt = $this->convertExcelTime($row['end_time']);

            if (!$startAt || !$endAt) {
                showWarningNotifiMessage('Invalid time format in start_time or end_time');
                return null; // Skip row with invalid times
            }

            // Find Branch ID by Name
            $branchId = null;
            if (!empty($row['branch'])) {
                $branch = Branch::where('name', $row['branch'])->first();
                if ($branch) {
                    $branchId = $branch->id;
                } else {
                    // Optional: Warning if branch not found?
                    showWarningNotifiMessage("Branch '{$row['branch']}' not found for shift '{$row['shift_name']}'");
                    // We might still want to create it without branch or skip?
                    // User said: "searches with what matches the name and takes the number"
                    // If not found, usually null or skip. I'll stick to null if not found for now unless critical.
                    return null;
                }
            }

            $this->successfulImportsCount++;
            return new WorkPeriod([
                'name' => $row['shift_name'],
                'description' => null, // Not in excel
                'start_at' => $startAt,
                'end_at' => $endAt,
                'day_and_night' => WorkPeriod::calculateDayAndNight($startAt, $endAt),
                'branch_id' => $branchId,
                'created_by' => auth()->id(),

            ]);
        } catch (Exception $e) {
            return null; // Skip row with error
        }
    }

    // WithHeadingRow automatically slugs headers.
    // Branch -> branch
    // Shift Name -> shift_name
    // Start Time -> start_time
    // End Time -> end_time

    public function headings(): array
    {
        return [
            'Branch',
            'Shift Name',
            'Start Time',
            'End Time',
        ];
    }

    // Getter for successful imports count
    public function getSuccessfulImportsCount(): int
    {
        return $this->successfulImportsCount;
    }

    public function rules(): array
    {
        return [
            'shift_name' => 'required|string|unique:hr_work_periods,name',
            'start_time' => 'required',
            'end_time' => 'required',
            'branch' => 'required',
        ];
    }


    private function convertExcelTime($value)
    {
        // Check if value is numeric (fractional day representation in Excel)
        if (is_numeric($value)) {
            // Convert numeric time (fractional day) to HH:mm:ss
            return Carbon::parse(Date::excelToDateTimeObject($value))->format('H:i:s');
        }

        // Attempt to parse as string time
        return Carbon::parse($value)->format('H:i:s');
    }
}
