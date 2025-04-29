<?php

namespace App\Filament\Resources\OcrResource\Pages;

use App\Filament\Resources\OcrResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOcr extends EditRecord
{
    protected static string $resource = OcrResource::class;

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
