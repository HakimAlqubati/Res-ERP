<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AdvanceRequestsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $records;

    public function __construct($records)
    {
        $this->records = $records;
    }

    public function collection()
    {
        return $this->records;
    }

    public function headings(): array
    {
        return [
            __('lang.id') ?? 'ID',
            __('lang.employee') ?? 'Employee',
            __('lang.branch') ?? 'Branch',
            __('lang.request_date') ?? 'Request Date',
            __('lang.advance_amount') ?? 'Advance Amount',
            __('lang.monthly_deduction') ?? 'Monthly Deduction',
            __('lang.starts_from') ?? 'Deduction Starts From',
            __('lang.ends_at') ?? 'Deduction Ends At',
            __('lang.number_of_months') ?? 'Months',
            __('lang.status') ?? 'Status',
            __('lang.approved_by') ?? 'Approved By',
        ];
    }

    public function map($record): array
    {
        $advance = $record->advanceRequest;

        return [
            $record->id,
            $record->employee?->name,
            $record->branch?->name,
            $record->application_date,
            $advance?->advance_amount,
            $advance?->monthly_deduction_amount,
            $advance?->deduction_starts_from,
            $advance?->deduction_ends_at,
            $advance?->number_of_months_of_deduction,
            $record->status,
            $record->approvedBy?->name,
        ];
    }
}
