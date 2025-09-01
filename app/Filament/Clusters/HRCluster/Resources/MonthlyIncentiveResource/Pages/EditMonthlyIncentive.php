<?php

namespace App\Filament\Clusters\HRCluster\Resources\MonthlyIncentiveResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Clusters\HRCluster\Resources\MonthlyIncentiveResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMonthlyIncentive extends EditRecord
{
    protected static string $resource = MonthlyIncentiveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

}
