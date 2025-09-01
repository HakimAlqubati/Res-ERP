<?php

namespace App\Filament\Resources\UserTypeResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\UserTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUserType extends EditRecord
{
    protected static string $resource = UserTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
