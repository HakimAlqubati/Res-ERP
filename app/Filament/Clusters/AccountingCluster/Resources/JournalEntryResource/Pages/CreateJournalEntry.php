<?php

namespace App\Filament\Clusters\AccountingCluster\Resources\JournalEntryResource\Pages;

use App\Filament\Clusters\AccountingCluster\Resources\JournalEntryResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateJournalEntry extends CreateRecord
{
    protected static string $resource = JournalEntryResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function beforeCreate(): void
    {
        $entry = $this->form->getModelInstance();
        // $data = $this->form->getState();
        // If lines aren't loaded yet, load them
        $entry->loadMissing('lines');

        $totalDebit = $entry->lines->sum('debit');
        $totalCredit = $entry->lines->sum('credit');
        
        if ($totalDebit !== $totalCredit) {
            Notification::make()
                ->title('Unbalanced Entry ⚖️')
                ->body("Total debit ({$totalDebit}) must equal total credit ({$totalCredit}).")
                ->danger()
                ->send();

            throw new \Filament\Support\Exceptions\Halt();
        }
    }


    // protected function beforeCreate(): void
    // {
    //     $lines = $this->form->getModelInstance()->lines ?? collect();

    //     $totalDebit = $lines->sum('debit');
    //     $totalCredit = $lines->sum('credit');

    //     if ($totalDebit !== $totalCredit) {
    //         showWarningNotifiMessage(
    //             'Balance Mismatch ❌',
    //             "The total debit ({$totalDebit}) must equal total credit ({$totalCredit})."
    //         );

    //         throw new \Filament\Support\Exceptions\Halt(); // This stops the creation process
    //     }
    // }
}
