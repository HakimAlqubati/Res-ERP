<?php

declare(strict_types=1);

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\HolidayOccurrences\Pages;

use App\Filament\Clusters\HRAttenanceCluster\Resources\HolidayOccurrences\HolidayOccurrenceResource;
use App\Models\HolidayOccurrence;
use App\Modules\HR\Holidays\Contracts\HolidayOccurrenceServiceInterface;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditHolidayOccurrence extends EditRecord
{
    protected static string $resource = HolidayOccurrenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var HolidayOccurrence $record */
        return app(HolidayOccurrenceServiceInterface::class)->update($record, $data, auth()->id());
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
