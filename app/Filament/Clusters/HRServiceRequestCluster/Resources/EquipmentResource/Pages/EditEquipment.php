<?php

namespace App\Filament\Clusters\HRServiceRequestCluster\Resources\EquipmentResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use App\Filament\Clusters\HRServiceRequestCluster\Resources\EquipmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEquipment extends EditRecord
{
    protected static string $resource = EquipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createServiceRequest')
                ->label('Report a Problem')
                ->icon('heroicon-o-wrench')
                ->url(fn() => route('filament.admin.h-r-service-request.resources.service-requests.create', [
                    'equipment_id' => $this->record->id,
                ]))
                ->openUrlInNewTab(),
            DeleteAction::make(),
        ];
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
       public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }
}
