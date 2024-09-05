<?php

namespace App\Filament\Clusters\HRCluster\Resources\PositionResource\Pages;

use App\Filament\Clusters\HRCluster\Resources\PositionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPositions extends ListRecords
{
    protected static string $resource = PositionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
