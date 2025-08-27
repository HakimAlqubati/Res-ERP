<?php

namespace App\Filament\Clusters\AreaManagementCluster\Resources\DistrictResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Clusters\AreaManagementCluster\Resources\DistrictResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDistrict extends EditRecord
{
    protected static string $resource = DistrictResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

}
