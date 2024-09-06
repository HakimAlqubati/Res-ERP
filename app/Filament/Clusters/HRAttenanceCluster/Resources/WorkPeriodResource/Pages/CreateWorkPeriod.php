<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\WorkPeriodResource\Pages;

use App\Filament\Clusters\HRAttenanceCluster\Resources\WorkPeriodResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateWorkPeriod extends CreateRecord
{
    protected static string $resource = WorkPeriodResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {

        $data['days'] = json_encode($data['days']);
        $data['created_by'] = auth()->user()->id;
        $data['updated_by'] = auth()->user()->id;
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
