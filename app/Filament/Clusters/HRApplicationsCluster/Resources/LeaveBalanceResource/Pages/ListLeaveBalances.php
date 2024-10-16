<?php

namespace App\Filament\Clusters\HRApplicationsCluster\Resources\LeaveBalanceResource\Pages;

use App\Filament\Clusters\HRApplicationsCluster\Resources\LeaveBalanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLeaveBalances extends ListRecords
{
    protected static string $resource = LeaveBalanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
