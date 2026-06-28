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
                \Filament\Forms\Components\Toggle::make('active')
                    ->default(true)
                    ->label(__('lang.active')),
            ]);
    }
}
