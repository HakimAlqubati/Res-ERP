<?php

namespace App\Filament\Clusters\HRCluster\Resources\AllowanceResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Clusters\HRCluster\Resources\AllowanceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAllowance extends EditRecord
{
    protected static string $resource = AllowanceResource::class;

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

    
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['is_percentage'] = $data['is_percentage'] == 'is_percentage' ? 1: 0;
        return $data;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['is_percentage'] = $data['is_percentage'] == 1 ? 'is_percentage': 'is_amount';
        return $data;
    }
}
