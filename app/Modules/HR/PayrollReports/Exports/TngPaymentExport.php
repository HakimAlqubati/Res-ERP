<?php

namespace App\Modules\HR\PayrollReports\Exports;

use App\Models\Payroll;
use App\Models\EmployeePaymentMethod;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Carbon\Carbon;

class TngPaymentExport implements FromQuery, WithMapping, WithHeadings, ShouldAutoSize
{
    protected Builder $query;

    public function __construct(Builder $query)
    {
        $this->query = $query;
    }

    public function query()
    {
        return $this->query;
    }

    public function map($payroll): array
    {
        $monthName = Carbon::create()->month($payroll->month)->format('F');
        $branchName = $payroll->branch?->name ?? $payroll->employee?->branch?->name ?? 'Unknown Branch';
        $rewardDescription = "Salary - {$monthName} {$payroll->year} - {$branchName}";
        
        $paymentDetails = $payroll->employee?->payment_details ?? [];
        $accountNumber = $paymentDetails['account_number'] ?? '';
        $rewardName = $paymentDetails['full_name'] ?? $payroll->employee?->name ?? '';
        
        // Truncate as per requested limits
        $rewardName = substr($rewardName, 0, 20);
        $rewardDescription = substr($rewardDescription, 0, 200);

        return [
            $accountNumber,
            $payroll->net_salary,
            $rewardName,
            $rewardDescription,
        ];
    }

    public function headings(): array
    {
        return [
            'eWallet Account Number',
            "Rm'",
            'Reward Name (Max 20 characters)',
            'Reward Description (Max 200 characters)',
        ];
    }
}
