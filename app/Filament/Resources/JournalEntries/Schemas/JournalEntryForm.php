<?php

namespace App\Filament\Resources\JournalEntries\Schemas;

use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;

class JournalEntryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make()->skippable()->columnSpanFull()
                    ->schema([
                        Step::make('basic')->columnSpanFull()->schema([
                            Section::make('Journal Entry Details')->columnSpanFull()
                                ->schema([
                                    Grid::make(3)
                                        ->schema([
                                            \Filament\Forms\Components\DatePicker::make('entry_date')
                                                ->required(),
                                            \Filament\Forms\Components\TextInput::make('reference_number')
                                                ->required(),
                                            \Filament\Forms\Components\TextInput::make('reference_type'),
                                            \Filament\Forms\Components\Select::make('currency_id')
                                                ->relationship('currency', 'currency_code')
                                                ->required(),
                                            \Filament\Forms\Components\Select::make('status')
                                                ->options([
                                                    'draft' => 'Draft',
                                                    'posted' => 'Posted',
                                                ])
                                                ->required(),
                                        ]),
                                    \Filament\Forms\Components\Textarea::make('description')
                                        ->columnSpanFull(),
                                ]),

                        ]),
                        Step::make('lines')->columnSpanFull()->schema([

                            \Filament\Forms\Components\Repeater::make('lines')
                                ->relationship()
                                ->table([
                                    TableColumn::make(__('lang.account')),
                                    TableColumn::make(__('lang.line_description')),
                                    TableColumn::make(__('lang.debit')),
                                    TableColumn::make(__('lang.credit')),
                                ])
                                ->schema([

                                    \Filament\Forms\Components\Select::make('account_id')
                                        ->relationship('account', 'account_name')
                                        ->required()
                                        ->searchable()
                                        ->columnSpan(2),
                                    \Filament\Forms\Components\TextInput::make('line_description')
                                        ->columnSpan(1),
                                    \Filament\Forms\Components\TextInput::make('debit')
                                        ->numeric()
                                        ->default(0),
                                    \Filament\Forms\Components\TextInput::make('credit')
                                        ->numeric()
                                        ->default(0),

                                ])
                                ->columns(1)
                                ->defaultItems(2)
                                ->grid(1),
                        ])
                    ]),



            ]);
    }
}
