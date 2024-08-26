<?php

namespace App\Filament\Resources;

use App\Models\FakeModelReports\StockReport;
use App\Filament\Resources\StockReportResource\Pages;
use App\Filament\Resources\StockReportResource\RelationManagers;
 
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StockReportResource extends Resource
{
    protected static ?string $model = StockReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

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
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListStockReports::route('/'),
            // 'create' => Pages\CreateStockReport::route('/create'),
            // 'edit' => Pages\EditStockReport::route('/{record}/edit'),
        ];
    }
}
