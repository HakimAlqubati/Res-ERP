<?php

declare(strict_types=1);

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\HolidayOccurrences\Pages;

use App\Filament\Clusters\HRAttenanceCluster\Resources\HolidayOccurrences\Actions\HolidayOccurrenceTableActions;
use App\Filament\Clusters\HRAttenanceCluster\Resources\HolidayOccurrences\HolidayOccurrenceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHolidayOccurrences extends ListRecords
{
    protected static string $resource = HolidayOccurrenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            HolidayOccurrenceTableActions::generateForYearAction(),
            CreateAction::make(),
        ];
    }
}
