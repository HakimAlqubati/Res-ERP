<?php

namespace App\Filament\Resources\MonthClosureResource\Pages;

use App\Filament\Resources\MonthClosureResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateMonthClosure extends CreateRecord
{
    protected static string $resource = MonthClosureResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}