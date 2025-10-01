<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use App\Services\HR\SalaryHelpers\SalarySlipService;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf as PDF;

class PayrollPdfController extends Controller
{
    public function show(Payroll $payroll)
    {
        $data_ = [
            'title' => 'تجربة PDF',
            'content' => 'هذا نص تجريبي للتأكد من أن مكتبة laravel-mpdf تعمل بشكل صحيح 🎉',
            'date' => now()->format('Y-m-d H:i:s'),
        ];

        // dd('sdf');
        /** @var SalarySlipService $service */
        $service = app(SalarySlipService::class);

        $payload = $service->build(
            employeeId: $payroll->employee_id,
            year: (int) $payroll->year,
            month: (int) $payroll->month,
        );

        $data = [
            'employee'             => $payload['employee']->toArray() ?? '',
            // 'title' => 'تجربة PDF',
            // 'content' => 'هذا نص تجريبي للتأكد من أن مكتبة laravel-mpdf تعمل بشكل صحيح 🎉',
            // 'date' => now()->format('Y-m-d H:i:s'),
            'branch'               => $payload['branch']->toArray() ?? '',
            'monthName'            => $payload['monthName'] ?? '',
            // 'data'                 => $payload['data'] ?? [],
            // 'employeeAllowances'   => $payload['employeeAllowances'] ?? [],
            // 'employeeDeductions'   => $payload['employeeDeductions'] ?? [],
            // 'totalAllowanceAmount' => (string) $payload['totalAllowanceAmount'] ?? '0',
            // 'totalDeductionAmount' => (string) $payload['totalDeductionAmount'] ?? '0',
        ];

        // dd($data, $data_);
        // return view('export.reports.hr.salaries.salary-slip', $data);
        // return view('pdf.test', $data);



        // $pdf = Pdf::loadView('pdf.test', $data);

        // تنزيل ملف
        // return $pdf->download('test.pdf');

        // $utf8Data = convertToUtf8($data);

        // dd('sdf', $utf8Data);
        $pdf = PDF::loadView('export.reports.hr.salaries.salary-slip', $data);

        return $pdf->download('export.reports.hr.salaries.salary-slip');
        // علامة مائية (اختياري)
        $mpdf = $pdf->getMpdf();
        $mpdf->SetWatermarkImage(public_path('storage/logo/default.png'), 0.06);

        $safeName = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $payload['employee']?->name ?? 'Employee');
        $filename = sprintf('SalarySlip-%s-%04d-%02d.pdf', $safeName, (int) $payroll->year, (int) $payroll->month);

        return $pdf->download($filename);
        // أو للعرض:
        // return $pdf->stream($filename);
    }
}
