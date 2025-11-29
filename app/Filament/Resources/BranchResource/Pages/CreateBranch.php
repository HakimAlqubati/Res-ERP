<?php

namespace App\Filament\Resources\BranchResource\Pages;


use App\Filament\Resources\BranchResource;
use App\Models\Branch;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;

class CreateBranch extends CreateRecord
{
    protected static string $resource = BranchResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if ($data['type'] === Branch::TYPE_HQ) {
            $existingHq = Branch::where('type', Branch::TYPE_HQ)->first();

            if ($existingHq) {

                showWarningNotifiMessage(__('lang.only_one_hq_allowed'));
                throw new Halt();
            }
        }

        return $data;
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
