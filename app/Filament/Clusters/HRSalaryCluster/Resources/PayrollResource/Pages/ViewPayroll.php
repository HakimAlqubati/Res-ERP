<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources\PayrollResource\Pages;

use App\Filament\Clusters\HRSalaryCluster\Resources\PayrollResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewPayroll extends ViewRecord
{
    protected static string $resource = PayrollResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\EditAction::make(),
            Actions\DeleteAction::make(),

        ];
    }
}
