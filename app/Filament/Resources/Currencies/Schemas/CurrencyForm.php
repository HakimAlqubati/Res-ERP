<?php

namespace App\Filament\Resources\Currencies\Schemas;

use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CurrencyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)->columnSpanFull()
                    ->schema([
                        TextInput::make('currency_code')
                            ->label(__('lang.currency_code'))
                            ->required()
                            ->maxLength(10)
                            ->unique(ignoreRecord: true)
                            ->placeholder('USD')
                            ->columnSpan(1),

                        TextInput::make('currency_name')
                            ->label(__('lang.currency_name'))
                            ->required()
                            ->maxLength(255)
                            ->placeholder(__('lang.us_dollar'))
                            ->columnSpan(1),

                        TextInput::make('symbol')
                            ->label(__('lang.currency_symbol'))
                            ->required()
                            ->maxLength(10)
                            ->placeholder('$')
                            ->columnSpan(1),

                        TextInput::make('exchange_rate')
                            ->label(__('lang.exchange_rate'))
                            ->required()
                            ->numeric()
                            ->default(1.000000)
                            ->minValue(0.000001)
                            ->step(0.000001)
                            ->helperText(__('lang.exchange_rate_help'))
                            ->columnSpan(1),

                        Toggle::make('is_base')
                            ->label(__('lang.is_base_currency'))
                            ->helperText(__('lang.base_currency_help'))
                            ->default(false)
                            ->columnSpan(2),
                    ]),
            ]);
    }
}
