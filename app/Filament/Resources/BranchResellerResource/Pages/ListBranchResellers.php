<?php

namespace App\Filament\Resources\BranchResellerResource\Pages;

use App\Filament\Resources\BranchResellerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBranchResellers extends ListRecords
{
    protected static string $resource = BranchResellerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}