<?php

namespace App\Filament\Resources\BranchSalesReports\Pages;

use App\Filament\Resources\BranchSalesReports\BranchSalesReportResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBranchSalesReports extends ListRecords
{
    protected static string $resource = BranchSalesReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
