<?php

namespace App\Filament\Resources\BranchResellerResource\Pages;

use App\Filament\Resources\BranchResellerResource;
use Filament\Actions; 
use Filament\Resources\Pages\ViewRecord;

class ViewBranchReseller extends ViewRecord
{
    protected static string $resource = BranchResellerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
        ];
    }
}