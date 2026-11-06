<?php

namespace App\Modules\HR\PayrollReports\Exports;

use App\Models\EwalletPaymentReport;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class CashPaymentExport implements FromCollection, WithMapping, WithHeadings, ShouldAutoSize
{
    protected EwalletPaymentReport $report;

    public function __construct(EwalletPaymentReport $report)
    {
        $this->report = $report;
        
        // Update the status of the report to 'exported' if it was pending
        if ($this->report->status === 'pending') {
            $this->report->update(['status' => 'exported']);
        }
    }

    public function collection()
    {
        // Get all items belonging to this report with employee loaded
        return $this->report->items()->with('employee')->get();
    }

    public function map($item): array
    {
        $employeeNo = $item->employee?->employee_no ?? $item->account_number ?? '-';
        $employeeName = $item->reward_name ?? $item->employee?->name ?? '';
        $rewardDescription = substr($item->reward_description ?? '', 0, 200);

        return [
            $employeeNo,
            $employeeName,
            $item->net_salary,
            $rewardDescription,
            '', // Blank space for physical signature upon cash handover
        ];
    }

    public function headings(): array
    {
        return [
            'Employee No',
            'Employee Name',
            "Net Salary (RM)",
            'Description',
            'Signature / Acknowledgment',
        ];
    }
}
