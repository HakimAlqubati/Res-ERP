<?php

namespace App\Filament\Resources\FinancialCategories\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Toggle;
use App\Models\FinancialCategory;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

class FinancialCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()->columnSpanFull()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Category Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->columnSpan(1),

                                Select::make('type')
                                    ->label('Type')
                                    ->options(FinancialCategory::TYPES)
                                    ->required()
                                    ->native(false)
                                    ->columnSpan(1),

                                Toggle::make('is_system')
                                    ->label('System Category')
                                    ->helperText('System categories are automatically created by the system')
                                    ->disabled(fn($record) => $record?->is_system === true)
                                    ->default(false)
                                    ->columnSpan(1),

                                Toggle::make('is_visible')
                                    ->label('Visible for Manual Entry')
                                    ->helperText('If disabled, this category will not appear in manual transaction forms')
                                    ->default(true)
                                    ->columnSpan(1),
                            ]),
                    ]),
            ]);
    }
}
