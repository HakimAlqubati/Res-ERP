<?php
namespace App\Filament\Resources;

use App\Filament\Clusters\SupplierCluster;
use App\Filament\Resources\StockIssueOrderResource\Pages;
use App\Models\StockIssueOrder;
use App\Models\Product;
use App\Models\Unit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class StockIssueOrderResource extends Resource
{
    protected static ?string $model = StockIssueOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $cluster = SupplierCluster::class;
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('order_date')->required(),
                Select::make('store_id')
                    ->relationship('store', 'name')
                    ->searchable()
                    ->required(),
                Select::make('issued_by')
                    ->relationship('issuedBy', 'name')
                    ->searchable()
                    ->required(),
                TextInput::make('notes')->maxLength(255),

                // **Repeater for Details**
                Repeater::make('details')
                    ->relationship('details')->columnSpanFull()
                    ->schema([
                        Select::make('product_id')
                            ->label('Product')
                            ->options(Product::all()->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        Select::make('unit_id')
                            ->label('Unit')
                            ->options(Unit::all()->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        TextInput::make('quantity')->numeric()->required(),
                    ])
                    ->columns(3)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_date')->sortable(),
                TextColumn::make('store.name')->label('Store')->sortable(),
                TextColumn::make('issuedBy.name')->label('Issued By')->sortable(),
                TextColumn::make('notes'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockIssueOrders::route('/'),
            'create' => Pages\CreateStockIssueOrder::route('/create'),
            'edit' => Pages\EditStockIssueOrder::route('/{record}/edit'),
        ];
    }
}
