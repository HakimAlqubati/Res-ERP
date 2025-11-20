<?php

namespace App\Filament\Clusters\FinanceFormattingCluster\Resources\Accounts\Schemas;

use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()->columnSpanFull()
                    ->schema([
                        \Filament\Forms\Components\TextInput::make('account_code')
                            ->required()
                            ->unique(ignoreRecord: true),
                        \Filament\Forms\Components\TextInput::make('account_name')
                            ->required(),
                        \Filament\Forms\Components\Select::make('account_type')
                            ->options([
                                'asset' => 'Asset',
                                'liability' => 'Liability',
                                'equity' => 'Equity',
                                'revenue' => 'Revenue',
                                'expense' => 'Expense',
                            ])
                            ->required(),
                        \Filament\Forms\Components\Select::make('parent_id')
                            ->relationship('parent', 'account_name')
                            ->searchable()
                            ->preload(),
                        \Filament\Forms\Components\Select::make('currency_id')
                            ->relationship('currency', 'currency_code')
                            ->searchable()
                            ->preload(),
                        \Filament\Forms\Components\Toggle::make('is_active')
                            ->default(true),
                        \Filament\Forms\Components\Toggle::make('allow_manual_entries')
                            ->default(true),
                        \Filament\Forms\Components\Toggle::make('is_parent')
                            ->default(false),
                    ])
            ]);
    }
}
