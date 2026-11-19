<?php

declare(strict_types=1);

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\HolidayTemplates\Pages;

use App\Filament\Clusters\HRAttenanceCluster\Resources\HolidayTemplates\HolidayTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHolidayTemplates extends ListRecords
{
    protected static string $resource = HolidayTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
