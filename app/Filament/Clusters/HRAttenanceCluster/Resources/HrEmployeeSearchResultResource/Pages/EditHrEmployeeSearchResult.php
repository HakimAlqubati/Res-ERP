<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\HrEmployeeSearchResultResource\Pages;

use App\Filament\Clusters\HRAttenanceCluster\Resources\HrEmployeeSearchResultResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHrEmployeeSearchResult extends EditRecord
{
    protected static string $resource = HrEmployeeSearchResultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
