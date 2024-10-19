<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\TestImageRecoResource\Pages;

use App\Filament\Clusters\HRAttenanceCluster\Resources\TestImageRecoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTestImageReco extends EditRecord
{
    protected static string $resource = TestImageRecoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
