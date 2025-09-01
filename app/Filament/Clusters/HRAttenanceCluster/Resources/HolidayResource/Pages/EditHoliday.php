<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\HolidayResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Clusters\HRAttenanceCluster\Resources\HolidayResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHoliday extends EditRecord
{
    protected static string $resource = HolidayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($data['from_date'] && $data['to_date']) {
            $daysDiff = now()->parse($data['from_date'])->diffInDays(now()->parse($data['to_date'])) + 1;
            $data['count_days'] = $daysDiff;
        } else {
            $data['count_days'] = 0;
        }

        $data['updated_by'] = auth()->user()->id;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
