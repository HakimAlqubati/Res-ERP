<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources\TngPaymentReportResource\Pages;

use App\Filament\Clusters\HRSalaryCluster\Resources\TngPaymentReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTngPaymentReports extends ListRecords
{
    protected static string $resource = TngPaymentReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Header actions are defined in the resource table for table-level context
        ];
    }
}
