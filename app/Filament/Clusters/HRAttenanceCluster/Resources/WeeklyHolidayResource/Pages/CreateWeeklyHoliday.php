<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\WeeklyHolidayResource\Pages;

use App\Filament\Clusters\HRAttenanceCluster\Resources\WeeklyHolidayResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateWeeklyHoliday extends CreateRecord
{
    protected static string $resource = WeeklyHolidayResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->user()->id;
        $data['updated_by'] = auth()->user()->id;
        $data['days'] = json_encode($data['days']);
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
