<?php

namespace App\Filament\Clusters\HRCluster\Resources\PositionResource\Pages;

use App\Filament\Clusters\HRCluster\Resources\PositionResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePosition extends CreateRecord
{
    protected static string $resource = PositionResource::class;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
