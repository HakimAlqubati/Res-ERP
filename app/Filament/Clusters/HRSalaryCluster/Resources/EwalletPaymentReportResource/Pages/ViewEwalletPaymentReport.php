<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources\EwalletPaymentReportResource\Pages;

use App\Filament\Clusters\HRSalaryCluster\Resources\EwalletPaymentReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewEwalletPaymentReport extends ViewRecord
{
    protected static string $resource = EwalletPaymentReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export_excel')
                ->label('Export Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function () {
                    $record = $this->getRecord();
                    $monthName = \Carbon\Carbon::create()->month($record->month)->format('F');
                    $fileName = "TnG_Payment_Report_{$monthName}_{$record->year}.xlsx";

                    return \Maatwebsite\Excel\Facades\Excel::download(
                        new \App\Modules\HR\PayrollReports\Exports\EwalletPaymentExport($record), 
                        $fileName
                    );
                }),
        ];
    }
}
