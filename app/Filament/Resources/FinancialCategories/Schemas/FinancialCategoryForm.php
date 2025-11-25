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
                        Grid::make(3)
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

                                TextInput::make('description')
                                    ->label('Description')
                                    ->maxLength(255)
                                    ->columnSpan(1),

                                Select::make('parent_id')
                                    ->label('Parent Category')
                                    ->relationship('parent', 'name', fn($query, $record) => $record ? $query->where('id', '!=', $record->id) : $query)
                                    ->searchable()
                                    ->preload()
                                    ->columnSpan(1),



                                Toggle::make('is_visible')
                                    ->label('Visible for Manual Entry')
                                    // ->helperText('If disabled, this category will not appear in manual transaction forms')
                                    ->default(true)->inline(false)
                                    ->columnSpan(1),
                            ]),
                    ]),
            ]);
    }
}
