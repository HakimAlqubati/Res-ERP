<?php

namespace App\Filament\Clusters\HRServiceRequestCluster\Resources\EquipmentResource\Pages;

use App\Filament\Clusters\HRServiceRequestCluster\Resources\EquipmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEquipment extends EditRecord
{
    protected static string $resource = EquipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('createServiceRequest')
                ->label('Add Service Request')
                ->icon('heroicon-o-wrench')
                ->url(fn() => route('filament.admin.h-r-service-request.resources.service-requests.create', [
                    'equipment_id' => $this->record->id,
                ]))
                ->openUrlInNewTab(),
            Actions\DeleteAction::make(),
        ];
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
