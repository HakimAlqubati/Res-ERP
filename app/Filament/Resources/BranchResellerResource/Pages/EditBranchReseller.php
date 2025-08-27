<?php

namespace App\Filament\Resources\BranchResellerResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\BranchResellerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBranchReseller extends EditRecord
{
    protected static string $resource = BranchResellerResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}