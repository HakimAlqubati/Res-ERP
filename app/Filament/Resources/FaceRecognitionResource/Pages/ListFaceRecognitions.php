<?php

namespace App\Filament\Resources\FaceRecognitionResource\Pages;

use App\Filament\Resources\FaceRecognitionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFaceRecognitions extends ListRecords
{
    protected static string $resource = FaceRecognitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action since it's read-only
            // Actions\CreateAction::make(),
        ];
    }
}
