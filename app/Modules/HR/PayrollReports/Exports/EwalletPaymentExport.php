<?php

namespace App\Modules\HR\PayrollReports\Exports;

use App\Models\EwalletPaymentReport;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class EwalletPaymentExport implements FromCollection, WithMapping, WithHeadings, ShouldAutoSize
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
        // Get all items belonging to this report
        return $this->report->items;
    }

    public function map($item): array
    {
        $rewardName = substr($item->reward_name ?? '', 0, 20);
        $rewardDescription = substr($item->reward_description ?? '', 0, 200);

        return [
            "'" . $item->account_number,
            $item->net_salary,
            $rewardName,
            $rewardDescription,
        ];
    }

    public function headings(): array
    {
        $isBank = $this->report->payment_type === 'bank';
        $accountLabel = $isBank ? 'Bank Account Number' : 'eWallet Account Number';

        return [
            $accountLabel,
            "Rm'",
            'Reward Name (Max 20 characters)',
            'Reward Description (Max 200 characters)',
        ];
    }
}
