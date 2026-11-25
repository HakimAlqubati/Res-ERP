<?php

declare(strict_types=1);

namespace App\Filament\Central\Resources\Settings\Pages;

use App\Filament\Central\Resources\Settings\SettingResource;
use App\Models\CentralSetting;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;

class CreateSetting extends CreateRecord
{
    protected static string $resource = SettingResource::class;

    protected static bool $canCreateAnother = false;

    public function mount(): void
    {
        parent::mount();

        $this->form->fill(CentralSetting::getAllSettings());
    }

    public function create(bool $another = false): void
    {
        $this->authorizeAccess();

        $data = $this->form->getState();

        CentralSetting::upsertSettings($data);

        $this->sendSuccessNotification('Done', 'Saved successfully');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save')
                ->submit('create')
                ->color('primary')
                ->keyBindings(['mod+s']),
            Action::make('cancel')
                ->url(static::getResource()::getUrl())
                ->label('Cancel'),
        ];
    }

    private function sendSuccessNotification(string $title, string $message): void
    {
        Notification::make()
            ->title($title)
            ->body($message)
            ->icon('heroicon-o-check-circle')
            ->duration(3000)
            ->iconColor('success')
            ->success()
            ->send();
    }

    public function getTitle(): string | Htmlable
    {
        return 'Central Settings';
    }

    public function getBreadcrumb(): string
    {
        return 'Manage';
    }
}
