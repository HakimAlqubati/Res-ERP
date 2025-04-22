<?php

namespace App\Filament\Clusters\AccountingCluster\Resources\JournalEntryResource\Pages;

use App\Filament\Clusters\AccountingCluster\Resources\JournalEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListJournalEntries extends ListRecords
{
    protected static string $resource = JournalEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
