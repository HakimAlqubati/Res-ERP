<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;


    protected function mutateFormDataBeforeFill(array $data): array
    {
        // $data['name'] = isset($data['name'][app()->getLocale()]) ? $data['name'][app()->getLocale()] : '';
        // $data['description'] =  isset($data['description'][app()->getLocale()]) ? $data['description'][app()->getLocale()] : '';

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
            // Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
            // ...
        ];
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
