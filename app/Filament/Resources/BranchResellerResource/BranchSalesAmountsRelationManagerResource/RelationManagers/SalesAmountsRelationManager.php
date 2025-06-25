<?php

namespace App\Filament\Resources\BranchResellerResource\BranchSalesAmountsRelationManagerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SalesAmountsRelationManager extends RelationManager
{
    protected static string $relationship = 'salesAmounts';

    protected static ?string $title = 'Sales History';
    // protected static ?string $badgeColor = Color::Blue;
    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return formatMoneyWithCurrency(
            $ownerRecord->salesAmounts()->sum('amount')
        );
    }
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                
            ]);
    }

    public function table(Table $table): Table
    {
        return $table->striped()
            ->recordTitleAttribute('amount')
            ->columns([
                TextColumn::make('amount')->formatStateUsing(fn($state) => formatMoneyWithCurrency($state))
                    ->alignCenter()
                    ->sortable()->summarize(Sum::make()),
                TextColumn::make('sales_at')
                    ->label('Sale Date')->sortable(),
                TextColumn::make('note')->wrap()->limit(50),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}