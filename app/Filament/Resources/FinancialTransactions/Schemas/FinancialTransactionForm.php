<?php

namespace App\Filament\Resources\FinancialTransactions\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Components\Utilities\Get;
use App\Models\FinancialCategory;
use App\Models\FinancialTransaction;
use App\Models\Branch;
use App\Models\PaymentMethod;

class FinancialTransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Transaction Details')->columnSpanFull()
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('category_id')
                                    ->label('Category')
                                    ->options(FinancialCategory::visible()->pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set) {
                                        if ($state) {
                                            $category = FinancialCategory::find($state);
                                            if ($category) {
                                                $set('type', $category->type);
                                            }
                                        }
                                    })
                                    ->columnSpan(1),

                                Hidden::make('type')
                                    ->default(FinancialCategory::TYPE_EXPENSE),

                                TextInput::make('amount')
                                    ->label('Amount')
                                    ->required()
                                    ->numeric()
                                    ->prefix(getDefaultCurrency())
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->columnSpan(1),

                                Select::make('status')
                                    ->label('Status')
                                    ->options(FinancialTransaction::STATUSES)
                                    ->required()
                                    ->default(FinancialTransaction::STATUS_PAID)
                                    ->native(false)
                                    ->columnSpan(1),

                                DatePicker::make('transaction_date')
                                    ->label('Transaction Date')
                                    ->required()
                                    ->default(now())
                                    ->native(false)
                                    ->columnSpan(1),

                                Select::make('month')
                                    ->label('Month')
                                    ->options([
                                        1 => 'January',
                                        2 => 'February',
                                        3 => 'March',
                                        4 => 'April',
                                        5 => 'May',
                                        6 => 'June',
                                        7 => 'July',
                                        8 => 'August',
                                        9 => 'September',
                                        10 => 'October',
                                        11 => 'November',
                                        12 => 'December',
                                    ])
                                    ->required()
                                    ->default(now()->month)
                                    ->native(false)
                                    ->columnSpan(1),

                                Select::make('year')
                                    ->label('Year')
                                    ->options(function () {
                                        $currentYear = now()->year;
                                        $years = [];
                                        for ($i = 0; $i < 4; $i++) {
                                            $year = $currentYear - $i;
                                            $years[$year] = $year;
                                        }
                                        return $years;
                                    })
                                    ->required()
                                    ->default(now()->year)
                                    ->native(false)
                                    ->columnSpan(1),

                                DatePicker::make('due_date')
                                    ->label('Due Date')
                                    ->visible(fn(Get $get) => $get('status') !== FinancialTransaction::STATUS_PAID)
                                    ->native(false)
                                    ->columnSpan(1),

                                Select::make('branch_id')
                                    ->label('Branch')
                                    ->options(Branch::active()->pluck('name', 'id'))
                                    ->searchable()
                                    ->nullable()
                                    ->columnSpan(1),

                                Select::make('payment_method_id')
                                    ->label('Payment Method')
                                    ->options(PaymentMethod::active()->pluck('name', 'id'))
                                    ->searchable()
                                    ->nullable()
                                    ->columnSpan(1),

                                Textarea::make('description')
                                    ->label('Description')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }
}
