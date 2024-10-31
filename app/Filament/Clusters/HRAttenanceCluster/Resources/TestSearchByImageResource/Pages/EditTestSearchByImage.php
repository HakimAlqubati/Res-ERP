<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\TestSearchByImageResource\Pages;

use App\Filament\Clusters\HRAttenanceCluster\Resources\TestSearchByImageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTestSearchByImage extends EditRecord
{
    protected static string $resource = TestSearchByImageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
