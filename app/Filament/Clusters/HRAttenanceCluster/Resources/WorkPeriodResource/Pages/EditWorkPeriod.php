<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\WorkPeriodResource\Pages;

use App\Filament\Clusters\HRAttenanceCluster\Resources\WorkPeriodResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWorkPeriod extends EditRecord
{
    protected static string $resource = WorkPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }


    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['days'] = json_decode($data['days']);
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['day_and_night'] = WorkPeriodResource::calculateDayAndNight($data['start_at'], $data['end_at']);
        return $data;
    }
}
