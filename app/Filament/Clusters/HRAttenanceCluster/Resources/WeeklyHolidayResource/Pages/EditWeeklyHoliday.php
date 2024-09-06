<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\WeeklyHolidayResource\Pages;

use App\Filament\Clusters\HRAttenanceCluster\Resources\WeeklyHolidayResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWeeklyHoliday extends EditRecord
{
    protected static string $resource = WeeklyHolidayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->user()->id;
        $data['days'] = json_encode($data['days']);
        return $data;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['days'] = json_decode($data['days']);
        return $data;
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
