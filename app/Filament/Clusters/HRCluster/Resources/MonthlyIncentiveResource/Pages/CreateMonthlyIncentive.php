<?php

namespace App\Filament\Clusters\HRCluster\Resources\MonthlyIncentiveResource\Pages;

use App\Filament\Clusters\HRCluster\Resources\MonthlyIncentiveResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateMonthlyIncentive extends CreateRecord
{
    protected static string $resource = MonthlyIncentiveResource::class;
}
