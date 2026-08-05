<?php

namespace App\Filament\Clusters\HRCluster\Resources\EmployeeServiceTerminations\Pages;

use App\Filament\Clusters\HRCluster\Resources\EmployeeServiceTerminations\EmployeeServiceTerminationResource;
use App\Models\EmployeeServiceTermination;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditEmployeeServiceTermination extends EditRecord
{
    protected static string $resource = EmployeeServiceTerminationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (in_array($this->record->status, [EmployeeServiceTermination::STATUS_APPROVED, EmployeeServiceTermination::STATUS_REJECTED])) {
            abort(403, 'This request is already ' . $this->record->status . ', and cannot be edited');
        }
        return $data;
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
