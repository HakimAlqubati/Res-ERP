<?php

namespace App\Filament\Resources\MonthClosureResource\Pages;

use App\Filament\Resources\MonthClosureResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMonthClosure extends EditRecord
{
    protected static string $resource = MonthClosureResource::class;

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