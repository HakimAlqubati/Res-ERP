<?php

namespace App\Filament\Resources\SystemSettingResource\Pages;

use App\Filament\Resources\SystemSettingResource;
use App\Models\Order;
use App\Models\SystemSetting;
use Filament\Forms\Components\Actions\Modal\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSystemSetting extends EditRecord
{
    protected static string $resource = SystemSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {

        $currentCaluclatingMethod = SystemSetting::select('calculating_orders_price_method')->first()?->calculating_orders_price_method;
        $ordersCount = Order::get()->first()?->id;

        if ($currentCaluclatingMethod != $data['calculating_orders_price_method'] && is_numeric($ordersCount)) {
            Notification::make()
                ->warning()
                ->title(__('system_settings.title_message_you_cannot_update_calculating_method'))
                ->body(__('system_settings.body_message_you_cannot_update_calculating_method'))
                ->persistent()

                ->send();
            $this->halt();
        }


        return $data;
    }


    // protected function beforeSave(): void
    // {

    //     Notification::make()
    //         ->warning()
    //         ->title('You don\'t have an active subscription!')
    //         ->body('Choose a plan to continue.')->persistent();

    //     $this->halt();
    // }
}
