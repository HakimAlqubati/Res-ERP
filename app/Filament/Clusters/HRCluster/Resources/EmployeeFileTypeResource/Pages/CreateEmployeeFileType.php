<?php

namespace App\Filament\Clusters\HRCluster\Resources\EmployeeFileTypeResource\Pages;

use App\Filament\Clusters\HRCluster\Resources\EmployeeFileTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;

class CreateEmployeeFileType extends CreateRecord
{
    protected static string $resource = EmployeeFileTypeResource::class;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public function getTitle(): string | Htmlable
    {
        if (filled(static::$title)) {
            return static::$title;
        }

        return __('filament-panels::resources/pages/create-record.title', [
            'label' => 'File type',
        ]);
    }
}
