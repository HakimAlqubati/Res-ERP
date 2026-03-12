<?php

namespace App\Filament\Resources\BranchSalesReports\Pages;

use App\Filament\Resources\BranchSalesReports\BranchSalesReportResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBranchSalesReport extends CreateRecord
{
    protected static string $resource = BranchSalesReportResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
