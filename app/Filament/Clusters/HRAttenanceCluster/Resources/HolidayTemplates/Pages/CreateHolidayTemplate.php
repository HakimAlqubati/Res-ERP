<?php

declare(strict_types=1);

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\HolidayTemplates\Pages;

use App\Filament\Clusters\HRAttenanceCluster\Resources\HolidayTemplates\HolidayTemplateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHolidayTemplate extends CreateRecord
{
    protected static string $resource = HolidayTemplateResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
