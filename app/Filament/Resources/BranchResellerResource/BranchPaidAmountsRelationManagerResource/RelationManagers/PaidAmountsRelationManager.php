<?php

namespace App\Filament\Resources\BranchResellerResource\BranchPaidAmountsRelationManagerResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaidAmountsRelationManager extends RelationManager
{
    protected static string $relationship = 'paidAmounts';
    protected static ?string $title = 'Payment History';
    // protected static ?string $badgeColor =  '#fff';
    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return formatMoneyWithCurrency(
            $ownerRecord->paidAmounts()->sum('amount')
        );
    }
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([]);
    }

    public function table(Table $table): Table
    {
        return $table->striped()
            ->recordTitleAttribute('amount')
            ->columns([
                TextColumn::make('amount')
                    ->formatStateUsing(fn($state) => formatMoneyWithCurrency($state))
                    ->sortable()->alignCenter()->summarize(Sum::make()),
                TextColumn::make('paid_at')->label('Payment Date')->sortable(),
                TextColumn::make('note')->wrap()->limit(50),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Tables\Actions\CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}