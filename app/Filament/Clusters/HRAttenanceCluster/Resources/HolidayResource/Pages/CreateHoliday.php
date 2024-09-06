<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\HolidayResource\Pages;

use App\Filament\Clusters\HRAttenanceCluster\Resources\HolidayResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateHoliday extends CreateRecord
{
    protected static string $resource = HolidayResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if ($data['from_date'] && $data['to_date']) {
            $daysDiff = now()->parse($data['from_date'])->diffInDays(now()->parse($data['to_date'])) + 1;
            $data['count_days'] = $daysDiff;
        } else {
            $data['count_days'] = 0;
        }

        $data['created_by'] = auth()->user()->id;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
