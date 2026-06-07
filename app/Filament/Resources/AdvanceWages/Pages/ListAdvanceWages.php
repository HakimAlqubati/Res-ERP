<?php

namespace App\Filament\Resources\AdvanceWages\Pages;

use App\Filament\Resources\AdvanceWages\AdvanceWageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAdvanceWages extends ListRecords
{
    protected static string $resource = AdvanceWageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
