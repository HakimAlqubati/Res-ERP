<?php

namespace App\Filament\Clusters\HRTasksSystem\Resources\DailyTasksSettingUpResource\Pages;

use App\Filament\Clusters\HRTasksSystem\Resources\DailyTasksSettingUpResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditDailyTasksSettingUp extends EditRecord
{
    protected static string $resource = DailyTasksSettingUpResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
     

    public function getTitle(): string | Htmlable
    {
        if (filled(static::$title)) {
            return static::$title;
        }

        return __('filament-panels::resources/pages/edit-record.title', [
            'label' => 'Scheduled task setup',
        ]);
    }
}
