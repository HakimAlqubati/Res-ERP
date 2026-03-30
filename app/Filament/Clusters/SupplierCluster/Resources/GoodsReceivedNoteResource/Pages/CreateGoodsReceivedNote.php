<?php

namespace App\Filament\Clusters\SupplierCluster\Resources\GoodsReceivedNoteResource\Pages;

use App\Filament\Clusters\SupplierCluster\Resources\GoodsReceivedNoteResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateGoodsReceivedNote extends CreateRecord
{
    protected static string $resource = GoodsReceivedNoteResource::class;
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
