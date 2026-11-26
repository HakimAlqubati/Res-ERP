<?php

declare(strict_types=1);

namespace App\Filament\Clusters\HRSalaryCluster\Resources\SalaryIncreaseRequests\Pages;

use App\Filament\Clusters\HRSalaryCluster\Resources\SalaryIncreaseRequests\SalaryIncreaseRequestResource;
use App\Filament\Support\FilamentValidationExceptionHandler;
use App\Modules\HR\SalaryIncrease\Contracts\SalaryIncreaseServiceInterface;
use App\Modules\HR\SalaryIncrease\DTOs\SalaryIncreaseDataDTO;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateSalaryIncreaseRequest extends CreateRecord
{
    protected static string $resource = SalaryIncreaseRequestResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(SalaryIncreaseServiceInterface::class)->create(
                SalaryIncreaseDataDTO::fromArray($data, auth()->id())
            );
        } catch (ValidationException $e) {
            FilamentValidationExceptionHandler::notifyAndRethrow($e);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
