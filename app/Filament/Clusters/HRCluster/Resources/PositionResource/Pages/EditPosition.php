<?php

namespace App\Filament\Clusters\HRCluster\Resources\PositionResource\Pages;

use App\Filament\Clusters\HRCluster\Resources\PositionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPosition extends EditRecord
{
    protected static string $resource = PositionResource::class;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
