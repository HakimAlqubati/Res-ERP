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
            EwalletPaymentReportResource::getExportExcelAction(Actions\Action::class),
            EwalletPaymentReportResource::getExportPdfAction(Actions\Action::class),
        ];
    }
}
