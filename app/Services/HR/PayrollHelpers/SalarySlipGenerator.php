<?php

namespace App\Services\HR\PayrollHelpers;

use App\Models\Allowance;
use App\Models\Employee;
use App\Models\MonthlySalaryIncreaseDetail;
use App\Models\MonthSalary;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf  as PDF;
class SalarySlipGenerator
{
    public function employeeSalarySlip($employeeId, $sid)
    {
        $monthSalary = MonthSalary::with([
            'details' => function ($query) use ($employeeId) {
                $query->where('employee_id', $employeeId);
            },
            'increaseDetails' => function ($query) use ($employeeId) {
                $query->where('employee_id', $employeeId);
            },
            'deducationDetails' => function ($query) use ($employeeId) {
                $query->where('employee_id', $employeeId);
            },
        ])->find($sid);

        return $monthSalary;
    }

    public function generateSalarySlipPdf($employeeId, $sid)
    {
        $employee = Employee::find($employeeId);
        $branch = $employee->branch;
        $data = employeeSalarySlip($employeeId, $sid);

        $increaseDetails = $data->increaseDetails;
        $deducationDetails = $data->deducationDetails;
        $allowanceTypes = Allowance::where('active', 1)->pluck('name', 'id')->toArray();
        $constAllowanceTypes = MonthlySalaryIncreaseDetail::ALLOWANCE_TYPES;
        $allallowanceTypes = $allowanceTypes + $constAllowanceTypes;
        $month = $data->month;
        $monthName = Carbon::parse($month)->translatedFormat('F Y');
        $allallowanceTypes = array_reverse($allallowanceTypes, true);
        $employeeAllowances = collect($increaseDetails)->map(function ($allowance) use ($allallowanceTypes) {
            return [
                // 'allowance_name' => $allallowanceTypes[$allowance['type_id']] ?? 'Unknown Allowance',
                'amount' => $allowance['amount'],
                'allowance_name' => $allowance['name'],
            ];
        });

        $employeeDeductions = collect($deducationDetails)->map(function ($deduction) {
            return [
                'deduction_name' => $deduction['deduction_name'] ?? 'Unknown Deduction',
                'deduction_amount' => $deduction['deduction_amount'],
            ];
        });

        $totalAllowanceAmount = $employeeAllowances->sum('amount') + ($data->details[0]['overtime_pay'] ?? 0) + ($employee->salary ?? 0) + ($data->details[0]['total_incentives'] ?? 0);
        $totalDeductionAmount = $employeeDeductions->sum('deduction_amount');

        $viewData = compact(
            'data',
            'totalAllowanceAmount',
            'totalDeductionAmount',
            'employeeAllowances',
            'employeeDeductions',
            'month',
            'monthName',
            'employee',
            'branch'
        );

        $pdf = Pdf::loadView('export.reports.hr.salaries.salary-slip', $viewData);
        return $pdf->output(); // Return PDF content
    }

    public function generateMultipleSalarySlips(array $employeeIds, $sid)
    {
         $pdfFiles = [];

        foreach ($employeeIds as $employeeId) {
            $employee = Employee::find($employeeId);
            $branch = $employee->branch;
            $data = employeeSalarySlip($employeeId, $sid);

            $month = $data->month;
            $monthName = Carbon::parse($month)->translatedFormat('F Y');

            $employeeAllowances = collect($data->increaseDetails)->map(function ($allowance) {
                return [
                    'allowance_name' => $allowance['type_id'] ?? 'Unknown Allowance',
                    'amount' => $allowance['amount'],
                ];
            })->toArray();

            $employeeDeductions = collect($data->deducationDetails)->map(function ($deduction) {
                return [
                    'deduction_name' => $deduction['deduction_id'] ?? 'Unknown Deduction',
                    'deduction_amount' => $deduction['deduction_amount'],
                ];
            })->toArray();

            $totalAllowanceAmount = collect($employeeAllowances)->sum('amount') + ($data->details[0]['overtime_pay'] ?? 0) + ($employee->salary ?? 0) + ($data->details[0]['total_incentives'] ?? 0);
            $totalDeductionAmount = collect($employeeDeductions)->sum('deduction_amount');

            // Prepare data for PDF
            $viewData = compact(
                'data',
                'totalAllowanceAmount',
                'totalDeductionAmount',
                'employeeAllowances',
                'employeeDeductions',
                'month',
                'monthName',
                'employee',
                'branch'
            );

            // Generate the PDF
            $pdf = Pdf::loadView('export.reports.hr.salaries.salary-slip', $viewData);

            // Save the PDF to a temporary location
            $fileName = "salary_slip_{$employee->name}.pdf";
            $filePath = "temp/{$fileName}";

            Storage::disk('public')->put($filePath, $pdf->output());

            $pdfFiles[] = [
                'name' => $fileName,
                'url' => Storage::url($filePath),
            ];
        }

        return $pdfFiles;
    }

    public function generateBulkSalarySlipPdf(array $employeeIds, $sid)
    {
                $mergedPdf = new Pdf; // For merging PDFs, consider libraries like `setasign/fpdi`.

        $zipFilePath = storage_path('app/public/salary_slips.zip'); // Temporary storage for ZIP file
        $zip = new \ZipArchive();

        if ($zip->open($zipFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            foreach ($employeeIds as $employeeId) {
                $employee = Employee::find($employeeId);
                $branch = $employee->branch;
                $data = employeeSalarySlip($employeeId, $sid);

                $increaseDetails = $data->increaseDetails;
                $deducationDetails = $data->deducationDetails;
                $monthName = Carbon::parse($data->month)->translatedFormat('F Y');

                $employeeAllowances = collect($increaseDetails)->map(function ($allowance) {
                    return [
                        'allowance_name' => $allowance['type_id'] ?? 'Unknown Allowance',
                        'amount' => $allowance['amount'],
                    ];
                })->toArray();

                $employeeDeductions = collect($deducationDetails)->map(function ($deduction) {
                    return [
                        'deduction_name' => $deduction['deduction_id'] ?? 'Unknown Deduction',
                        'deduction_amount' => $deduction['deduction_amount'],
                    ];
                })->toArray();

                $totalAllowanceAmount = collect($employeeAllowances)->sum('amount');
                $totalDeductionAmount = collect($employeeDeductions)->sum('deduction_amount');

                $viewData = compact(
                    'employee',
                    'branch',
                    'data',
                    'employeeAllowances',
                    'employeeDeductions',
                    'totalAllowanceAmount',
                    'totalDeductionAmount',
                    'monthName'
                );

                $pdf = Pdf::loadView('export.reports.hr.salaries.salary-slip', $viewData);

                // Save individual PDF in the ZIP file
                $fileName = "salary_slip_{$employee->name}.pdf";
                $zip->addFromString($fileName, $pdf->output());
            }

            $zip->close();

            return response()->download($zipFilePath)->deleteFileAfterSend(true);
        } else {
            throw new \Exception('Failed to create ZIP file.');
        }
    }

    public function convertToUtf8($data)
    {
        if (is_array($data)) {
            return array_map('convertToUtf8', $data);
        } elseif (is_object($data)) {
            foreach (get_object_vars($data) as $key => $value) {
                $data->$key = convertToUtf8($value);
            }
            return $data;
        } elseif (is_string($data)) {
            // Ensure the string is properly encoded in UTF-8
            return mb_convert_encoding($data, 'UTF-8', 'UTF-8');
        }
        // Return other data types (e.g., int, float) as-is
        return $data;
    }
}