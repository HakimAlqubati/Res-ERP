<?php

namespace App\Filament\Clusters\HRCircularCluster\Resources\CircularResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Clusters\HRCircularCluster\Resources\CircularResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCircular extends EditRecord
{
    protected static string $resource = CircularResource::class;

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
