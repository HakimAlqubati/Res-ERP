<?php

namespace App\Imports;

use Exception;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use App\Models\Employee;
use App\Models\WorkPeriod;
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

class WorkPeriodImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use SkipsFailures;
    private $successfulImportsCount = 0; //
    public function model(array $row)
    {
        try {
            //code...
            if (!isset($row['name']) || empty($row['name'])) {
                showWarningNotifiMessage('Shift name is missing');
                return null; // Skip row
            }

            // Parse and validate time fields
            $startAt = $this->convertExcelTime($row['start_at']);
            $endAt = $this->convertExcelTime($row['end_at']);

            if (!$startAt || !$endAt) {
                showWarningNotifiMessage('Invalid time format in start_at or end_at');
                return null; // Skip row with invalid times
            }
            $this->successfulImportsCount++;
            return new WorkPeriod([
                'name' => $row['name'],
                'description' => $row['description'] ?? null,
                'start_at' => $startAt,
                'end_at' => $endAt,
                'day_and_night' => $row['day_and_night'] ?? 0,
                'branch_id' => $row['branch_id'],
                'days' => $row['days'] ?? '["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"]',
                'created_by' => auth()->id(),

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
            'description',
            'start_at',
            'end_at',
            'day_and_night',
            'branch_id',
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
            'name' => 'required|string|unique:hr_work_periods,name',
            'start_at' => 'required',
            'end_at' => 'required',
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
