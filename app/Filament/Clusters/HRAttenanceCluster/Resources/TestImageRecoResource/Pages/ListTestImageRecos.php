<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\TestImageRecoResource\Pages;

use App\Filament\Clusters\HRAttenanceCluster\Resources\TestImageRecoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTestImageRecos extends ListRecords
{
    protected static string $resource = TestImageRecoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
