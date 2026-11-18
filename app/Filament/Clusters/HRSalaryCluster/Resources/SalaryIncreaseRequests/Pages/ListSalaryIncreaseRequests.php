<?php

declare(strict_types=1);

namespace App\Filament\Clusters\HRSalaryCluster\Resources\SalaryIncreaseRequests\Pages;

use App\Filament\Clusters\HRSalaryCluster\Resources\SalaryIncreaseRequests\SalaryIncreaseRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSalaryIncreaseRequests extends ListRecords
{
    protected static string $resource = SalaryIncreaseRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
