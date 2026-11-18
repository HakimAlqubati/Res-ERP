<?php

declare(strict_types=1);

namespace App\Filament\Clusters\HRSalaryCluster\Resources\SalaryIncreaseRequests\Pages;

use App\Filament\Clusters\HRSalaryCluster\Resources\SalaryIncreaseRequests\SalaryIncreaseRequestResource;
use App\Modules\HR\SalaryIncrease\Contracts\SalaryIncreaseServiceInterface;
use App\Modules\HR\SalaryIncrease\DTOs\SalaryIncreaseDataDTO;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateSalaryIncreaseRequest extends CreateRecord
{
    protected static string $resource = SalaryIncreaseRequestResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(SalaryIncreaseServiceInterface::class)->create(
            SalaryIncreaseDataDTO::fromArray($data, auth()->id())
        );
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
