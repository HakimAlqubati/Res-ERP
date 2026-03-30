<?php

namespace App\Filament\Resources\PurchaseInvoiceResource\Pages;

use App\Filament\Resources\PurchaseInvoiceResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchaseInvoice extends CreateRecord
{
    protected static string $resource = PurchaseInvoiceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // dd($data['purchaseInvoiceDetails']);
        return $data;
    }

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
