<?php

namespace App\Filament\Clusters\HRCluster\Resources\DeductionResource\Pages;

use App\Filament\Clusters\HRCluster\Resources\DeductionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDeduction extends EditRecord
{
    protected static string $resource = DeductionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
