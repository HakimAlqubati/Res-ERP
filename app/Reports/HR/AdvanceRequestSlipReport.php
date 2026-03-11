<?php

namespace App\Reports\HR;

use App\Models\EmployeeApplicationV2;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf;

class AdvanceRequestSlipReport
{
    /**
     * Generate and download the Advance Request Slip PDF.
     * 
     * @param int|string $applicationId
     * @return \Illuminate\Http\Response
     */
    public function generate($applicationId)
    {
        $application = EmployeeApplicationV2::with([
            'employee',
            'employee',
            'employee.department',
            'employee.branch',
            'advanceRequest.installments',
            'approvedBy',
        ])->findOrFail($applicationId);

        $advance = $application->advanceRequest;
        $installments = $advance ? $advance->installments()->orderBy('sequence')->get() : collect();

        $data = [
            'application' => $application,
            'advance' => $advance,
            'installments' => $installments,
        ];

        $pdf = LaravelMpdf::loadView('reports.hr.applications.advance-request-slip-pdf', $data);

        $filename = sprintf(
            'AdvanceRequestSlip-%s-%s.pdf',
            $application->employee?->name ?? '000',
            $application->id
        );

        // Use streamDownload for Livewire compatibility
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename);
    }
}
