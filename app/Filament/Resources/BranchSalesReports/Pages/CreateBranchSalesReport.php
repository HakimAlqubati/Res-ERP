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

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $reports = $data['reports'] ?? [];
        $firstRecord = null;

        foreach ($reports as $reportData) {
            $record = static::getModel()::create($reportData);
            
            if ($firstRecord === null) {
                $firstRecord = $record;
            }

            // Sync document analysis attempt
            if (isset($reportData['document_analysis_attempt_id']) && $reportData['document_analysis_attempt_id']) {
                \App\Models\DocumentAnalysisAttempt::where('id', $reportData['document_analysis_attempt_id'])->update([
                    'documentable_id' => $record->id,
                    'documentable_type' => get_class($record),
                ]);
            }
        }

        return $firstRecord ?? static::getModel()::create([]);
    }
}
