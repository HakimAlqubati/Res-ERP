<?php

namespace App\Filament\Resources\ReturnedOrderResource\Pages;

use App\Filament\Resources\ReturnedOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateReturnedOrder extends CreateRecord
{
    protected static string $resource = ReturnedOrderResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
