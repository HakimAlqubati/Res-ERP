<?php

namespace App\Filament\Resources\MonthClosureResource\Pages;

use App\Filament\Resources\MonthClosureResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMonthClosures extends ListRecords
{
    protected static string $resource = MonthClosureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
