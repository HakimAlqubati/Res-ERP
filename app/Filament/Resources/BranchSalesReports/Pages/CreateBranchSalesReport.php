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

    protected function afterCreate(): void
    {
        if (isset($this->data['document_analysis_attempt_id']) && $this->data['document_analysis_attempt_id']) {
            \App\Models\DocumentAnalysisAttempt::where('id', $this->data['document_analysis_attempt_id'])->update([
                'documentable_id' => $this->record->id,
                'documentable_type' => get_class($this->record),
            ]);
        }
    }
}
