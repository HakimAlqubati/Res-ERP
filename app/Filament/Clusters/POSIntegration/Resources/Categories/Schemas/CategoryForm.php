<?php

namespace App\Filament\Clusters\POSIntegration\Resources\Categories\Schemas;

use App\Models\Category;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Fieldset::make()->columnSpanFull()->schema([
                    Grid::make()->columnSpanFull()->columns(3)->schema([
                        TextInput::make('name')
                            ->unique(ignoreRecord: true)
                            ->required()->label(__('lang.name')),
                        // Forms\Components\TextInput::make('code')
                        //     ->unique(ignoreRecord: true)
                        //     ->required()->label(__("lang.code")),
                        TextInput::make('code_starts_with')
                            ->label('Code Starts With')
                            ->maxLength(5)
                            ->unique(ignoreRecord: true)
                            ->required()
                            ->maxLength(2)
                            ->minLength(2)
                            ->rule('regex:/^[0-9]{2}$/')
                            ->placeholder(function () {
                                $lastCode = Category::query()
                                    ->whereRaw('code_starts_with REGEXP "^[0-9]{2}$"') // فقط الأرقام
                                    ->orderByDesc('code_starts_with')
                                    ->value('code_starts_with');

                                $nextCode = str_pad((intval($lastCode) + 1), 2, '0', STR_PAD_LEFT);
                                return $nextCode;
                            })

                            ->helperText('Code must be exactly 2 digits (e.g., 01, 25, 99)'),
                        // Forms\Components\TextInput::make('waste_stock_percentage')
                        //     ->label('Waste %')
                        //     ->numeric()
                        //     ->default(0)
                        //     ->minValue(0)
                        //     ->maxValue(100)
                        //     ->helperText('Expected stock waste percentage for this category.'),
                    ]),
                    Grid::make()->columnSpanFull()->columns(3)->schema([
                        Toggle::make('active')
                            ->inline(false)->default(true)
                            ->label(__("lang.active")),
                     
                        Toggle::make('has_description')
                            ->label('Has Description')->inline(false)->live(),
                    ]),
                    Textarea::make('description')
                        ->visible(fn($get): bool => $get('has_description'))
                        ->label(__("lang.description"))->columnSpanFull()
                        ->rows(10)
                        ->cols(20),
                ])

            ]);
    }
}
