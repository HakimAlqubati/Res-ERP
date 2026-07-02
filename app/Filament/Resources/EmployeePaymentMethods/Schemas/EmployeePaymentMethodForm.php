<?php

namespace App\Filament\Resources\EmployeePaymentMethods\Schemas;

use Filament\Schemas\Schema;

class EmployeePaymentMethodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label(__('lang.name')),
                \Filament\Forms\Components\Select::make('code')
                    ->options(collect(\App\Models\EmployeePaymentMethod::getCodes())->pluck('value', 'key')->toArray())
                    ->label(__('lang.code'))
                    ->unique(ignoreRecord: true)
                    ->nullable(),
                \Filament\Forms\Components\Toggle::make('active')
                    ->default(true)
                    ->label(__('lang.active')),
            ]);
    }
}
