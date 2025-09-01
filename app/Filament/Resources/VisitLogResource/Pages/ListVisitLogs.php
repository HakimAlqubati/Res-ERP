<?php

namespace App\Filament\Resources\VisitLogResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\VisitLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVisitLogs extends ListRecords
{
    protected static string $resource = VisitLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
