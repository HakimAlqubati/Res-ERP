<?php
namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use App\Models\ReturnedOrder;
use App\Models\Order;
use App\Models\StockAdjustmentDetail;
use App\Models\StockIssueOrder;
use App\Models\StockTransferOrder;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\StockCostReportResource\Pages\ListStockCostReports;
use App\Filament\Resources\StockCostReportResource\Pages;
use App\Models\Product;
use App\Models\Store;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StockCostReportResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

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
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                    ->schema([
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
                        ReturnedOrder::class         => 'Returned Order',
                        Order::class                 => 'Order',
                        StockAdjustmentDetail::class => 'Stock Adjustment',
                        StockIssueOrder::class       => 'Stock Issue Order',
                        StockTransferOrder::class       => 'Stock Transfer Order',
                    ])->multiple()
                    ->searchable()
                    ->placeholder('All Types'),

                    ],FiltersLayout::AboveContent)
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
            'index' => ListStockCostReports::route('/'),

        ];
    }
}