<?php

namespace App\Filament\Clusters\HRCircularCluster\Resources\CircularResource\Pages;

use App\Filament\Clusters\HRCircularCluster\Resources\CircularResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCirculars extends ListRecords
{
    protected static string $resource = CircularResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
