<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\StockReportResource\Pages\ListStockReports;
use App\Models\FakeModelReports\StockReport;
use App\Filament\Resources\StockReportResource\Pages;
use App\Filament\Resources\StockReportResource\RelationManagers;
 
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StockReportResource extends Resource
{
    protected static ?string $model = StockReport::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

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
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
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
            'index' => ListStockReports::route('/'),
            // 'create' => Pages\CreateStockReport::route('/create'),
            // 'edit' => Pages\EditStockReport::route('/{record}/edit'),
        ];
    }
}
