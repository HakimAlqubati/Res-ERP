<?php

namespace App\Filament\Clusters\HRServiceRequestCluster\Resources\ServiceRequestResource\Pages;

use App\Filament\Clusters\HRServiceRequestCluster\Resources\ServiceRequestResource;
use App\Models\ServiceRequest;
use Filament\Resources\Pages\CreateRecord;

class CreateServiceRequest extends CreateRecord
{
    protected static string $resource = ServiceRequestResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = ServiceRequest::STATUS_NEW;
        $data['created_by'] = auth()->user()->id;
        return $data;
    }

    public function afterCreate(): void
    {
        if (is_array($this->data['file_path']) && count($this->data['file_path']) > 0) {
            foreach ($this->data['file_path'] as $key => $image) {
                $this->record->photos()->create([
                    'image_name' => $image,
                    'image_path' => $image,
                    'created_by' => auth()->user()->id,
                ]);
            }
        }

    }
}
