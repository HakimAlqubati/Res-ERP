<?php

namespace App\Filament\Resources\OcrResource\Pages;

use App\Filament\Resources\OcrResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOcrs extends ListRecords
{
    protected static string $resource = OcrResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
