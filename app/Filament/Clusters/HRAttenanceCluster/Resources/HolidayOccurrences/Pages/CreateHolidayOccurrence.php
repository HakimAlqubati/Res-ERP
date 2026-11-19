<?php

declare(strict_types=1);

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\HolidayOccurrences\Pages;

use App\Filament\Clusters\HRAttenanceCluster\Resources\HolidayOccurrences\HolidayOccurrenceResource;
use App\Modules\HR\Holidays\Contracts\HolidayOccurrenceServiceInterface;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateHolidayOccurrence extends CreateRecord
{
    protected static string $resource = HolidayOccurrenceResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(HolidayOccurrenceServiceInterface::class)->create($data, auth()->id());
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
