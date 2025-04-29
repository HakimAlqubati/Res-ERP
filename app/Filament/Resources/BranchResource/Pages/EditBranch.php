<?php

namespace App\Filament\Resources\BranchResource\Pages;

use App\Filament\Resources\BranchResource;
use App\Models\Branch;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;

class EditBranch extends EditRecord
{
    protected static string $resource = BranchResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($data['type'] === Branch::TYPE_HQ) {
            $existingHq = Branch::where('type', Branch::TYPE_HQ)->first();

            if ($existingHq && $this->record->id != $existingHq->id && $existingHq->active) {

                showWarningNotifiMessage(__('lang.only_one_hq_allowed'));
                throw new Halt();
            }
        }
        return $data;
    }
}
