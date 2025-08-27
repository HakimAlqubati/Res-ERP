<?php

namespace App\Filament\Clusters\HRServiceRequestCluster\Resources\ServiceRequestResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Clusters\HRServiceRequestCluster\Resources\ServiceRequestResource;
use App\Models\ServiceRequestLog;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewServiceRequest extends ViewRecord
{
    protected static string $resource = ServiceRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
  
}
