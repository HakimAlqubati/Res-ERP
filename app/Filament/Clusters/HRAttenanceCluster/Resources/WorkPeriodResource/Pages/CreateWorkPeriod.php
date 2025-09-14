<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\WorkPeriodResource\Pages;

use App\Filament\Clusters\HRAttenanceCluster\Resources\WorkPeriodResource;
use App\Models\WorkPeriod;
use Filament\Resources\Pages\CreateRecord;

class CreateWorkPeriod extends CreateRecord
{
    protected static string $resource = WorkPeriodResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['days'] = json_encode(['sun']);
        $data['created_by'] = auth()->user()->id;
        $data['updated_by'] = auth()->user()->id;
        $data['day_and_night'] = WorkPeriodResource::calculateDayAndNight($data['start_at'], $data['end_at']);
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}