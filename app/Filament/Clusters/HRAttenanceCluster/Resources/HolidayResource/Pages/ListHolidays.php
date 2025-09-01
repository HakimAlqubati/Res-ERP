<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\HolidayResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Clusters\HRAttenanceCluster\Resources\HolidayResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHolidays extends ListRecords
{
    protected static string $resource = HolidayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
