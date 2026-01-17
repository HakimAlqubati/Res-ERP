<?php

namespace App\Filament\Clusters\CashBoxAndBank\Resources\CashBoxes\Schemas;

use App\Models\User;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CashBoxForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('lang.cash_box_info'))->columnSpanFull()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label(__('lang.cash_box_name'))
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder(__('lang.cash_box_name_placeholder'))
                                    ->columnSpan(2),

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
                                        return $query->where('account_type', 'asset');
                                        // ->where('allow_manual_entries', true); // Disabled for now
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->helperText(__('lang.gl_account_help'))
                                    ->columnSpan(1),

                                Select::make('keeper_id')
                                    ->label(__('lang.keeper'))
                                    ->relationship('keeper', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->helperText(__('lang.keeper_help'))
                                    ->columnSpan(1),

                                TextInput::make('max_limit')
                                    ->label(__('lang.max_limit'))
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->helperText(__('lang.max_limit_help'))
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
