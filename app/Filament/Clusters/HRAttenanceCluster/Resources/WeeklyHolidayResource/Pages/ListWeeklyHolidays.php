<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\WeeklyHolidayResource\Pages;

use App\Filament\Clusters\HRAttenanceCluster\Resources\WeeklyHolidayResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWeeklyHolidays extends ListRecords
{
    protected static string $resource = WeeklyHolidayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
