<?php

declare(strict_types=1);

namespace App\Filament\Clusters\HRSalaryCluster\Resources\SalaryIncreaseRequests\Pages;

use App\Filament\Clusters\HRSalaryCluster\Resources\SalaryIncreaseRequests\SalaryIncreaseRequestResource;
use App\Modules\HR\SalaryIncrease\Contracts\SalaryIncreaseServiceInterface;
use App\Modules\HR\SalaryIncrease\DTOs\SalaryIncreaseDataDTO;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditSalaryIncreaseRequest extends EditRecord
{
    protected static string $resource = SalaryIncreaseRequestResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (! $this->record->isPending()) {
            abort(403, 'Only pending salary increase requests can be edited.');
        }

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $data['employee_id'] = $record->employee_id;

        return app(SalaryIncreaseServiceInterface::class)->update(
            $record->id,
            SalaryIncreaseDataDTO::fromArray($data, auth()->id())
        );
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
