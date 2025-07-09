<?php
namespace App\Filament\Resources;

use App\Filament\Resources\StockCostReportResource\Pages;
use App\Models\Product;
use App\Models\Store;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StockCostReportResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationLabel(): string
    {
        return 'Stock Cost Report';
    }

    public static function getPluralLabel(): ?string
    {
        return 'Stock Cost Report';
    }

    public static function getLabel(): ?string
    {
        return 'Stock Cost Report';
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                Filter::make('date')
                    ->form([
                        DatePicker::make('from_date')->label('From Date')->default(now()->startOfMonth()),
                        DatePicker::make('to_date')->label('To Date')->default(now()),
                    ]),

                SelectFilter::make('store_id')
                    ->label(__('lang.store'))
                    ->searchable()
                    ->placeholder('Choose')
                    ->options(
                        Store::active()->pluck('name', 'id')->toArray()
                    ),
                SelectFilter::make('returnable_type')
                    ->label('Out Type')
                    ->options([
                        \App\Models\ReturnedOrder::class         => 'Returned Order',
                        \App\Models\Order::class                 => 'Order',
                        \App\Models\StockAdjustmentDetail::class => 'Stock Adjustment',
                        \App\Models\StockIssueOrder::class       => 'Stock Issue Order',
                    ])->multiple()
                    ->searchable()
                    ->placeholder('All Types'),

                    ],FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockCostReports::route('/'),

        ];
    }
}