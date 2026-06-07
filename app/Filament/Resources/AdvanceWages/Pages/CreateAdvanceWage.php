<?php

namespace App\Filament\Resources\AdvanceWages\Pages;

use App\Filament\Resources\AdvanceWages\AdvanceWageResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAdvanceWage extends CreateRecord
{
    protected static string $resource = AdvanceWageResource::class;
      protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
