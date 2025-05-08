<?php

namespace App\Filament\Clusters\HRServiceRequestCluster\Resources\ServiceRequestResource\Pages;

use App\Filament\Clusters\HRServiceRequestCluster\Resources\ServiceRequestResource;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestLog;
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
        if (isStuff()) {
            $data['branch_id'] = auth()->user()->branch_id;
        }
        $data['created_by'] = auth()->user()->id;
        return $data;
    }

    public function afterCreate(): void
    {
        $this->record->logs()->create([
            'created_by' => auth()->user()->id,
            'description' => 'Service request has been created',
            'log_type' => ServiceRequestLog::LOG_TYPE_CREATED,
        ]);
    }
}
