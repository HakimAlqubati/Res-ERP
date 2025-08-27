<?php
namespace App\Filament\Resources\ManufacturingBranchResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\ManufacturingBranchResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditManufacturingBranch extends EditRecord
{
    protected static string $resource = ManufacturingBranchResource::class;

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