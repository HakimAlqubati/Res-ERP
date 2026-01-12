<?php

namespace App\Filament\Clusters\CashBoxAndBank\Resources\BankAccounts\Schemas;

use App\Models\Account;
use App\Models\Currency;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class BankAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('lang.bank_account_info'))->columnSpanFull()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label(__('lang.bank_account_name'))
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder(__('lang.bank_account_name_placeholder'))
                                    ->columnSpan(2),

                                TextInput::make('account_number')
                                    ->label(__('lang.account_number'))
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('123456789')
                                    ->columnSpan(1),

                                TextInput::make('iban')
                                    ->label(__('lang.iban'))
                                    ->maxLength(255)
                                    ->placeholder('SA00 0000 0000 0000 0000 0000')
                                    ->columnSpan(1),

                                Select::make('currency_id')
                                    ->label(__('lang.currency'))
                                    ->required()
                                    ->relationship('currency', 'currency_name')
                                    ->searchable()
                                    ->preload()
                                    ->columnSpan(1),

                                Select::make('gl_account_id')
                                    ->label(__('lang.gl_control_account'))
                                    ->required()
                                    ->relationship('glAccount', 'account_name', function ($query) {
                                        return $query->where('account_type', 'asset')
                                            ->where('allow_manual_entries', true);
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->helperText(__('lang.gl_account_help'))
                                    ->columnSpan(1),

                                Toggle::make('is_active')
                                    ->label(__('lang.is_active'))
                                    ->default(true)
                                    ->columnSpan(2),
                            ]),
                    ]),
            ]);
    }
}
