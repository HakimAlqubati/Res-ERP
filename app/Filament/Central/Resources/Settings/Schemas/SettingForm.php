<?php

declare(strict_types=1);

namespace App\Filament\Central\Resources\Settings\Schemas;

use App\Models\CentralSetting;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class SettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('central_settings')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('Tenant Billing & Invoices')
                            ->icon(Heroicon::OutlinedDocumentText)
                            ->schema([
                                Fieldset::make('Invoice Settings')
                                    ->columns(['default' => 1, 'md' => 2])
                                    ->schema([
                                        TextInput::make(CentralSetting::KEY_DEFAULT_INVOICE_WARNING_DAYS)
                                            ->label('Default Invoice Warning Days')
                                            ->helperText('Default number of warning days pre-filled when creating a new tenant invoice.')
                                            ->prefixIcon(Heroicon::OutlinedBellAlert)
                                            ->suffix('days')
                                            ->numeric()
                                            ->integer()
                                            ->minValue(0)
                                            ->maxValue(365)
                                            ->default(CentralSetting::DEFAULT_INVOICE_WARNING_DAYS)
                                            ->required(),

                                        Toggle::make(CentralSetting::KEY_SUSPEND_TENANT_ON_OVERDUE_INVOICE)
                                            ->label('Suspend Tenant on Overdue Invoice')
                                            ->helperText('Suspend tenant access once an unpaid invoice passes its due date (setting only; not enforced yet).')
                                            ->inline(false)
                                            ->default(CentralSetting::DEFAULT_SUSPEND_TENANT_ON_OVERDUE_INVOICE),
                                    ]),
                            ]),
                    ]),
            ]);
    }
}
