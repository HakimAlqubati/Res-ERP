<?php

namespace App\Filament\Resources\BranchSalesReports\Pages;

use App\Filament\Resources\BranchSalesReports\BranchSalesReportResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditBranchSalesReport extends EditRecord
{
    protected static string $resource = BranchSalesReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
