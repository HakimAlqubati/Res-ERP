<?php

namespace App\Filament\Clusters\SupplierCluster\Resources\PurchaseInvoiceResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DetailsRelationManager extends RelationManager
{
    protected static string $relationship = 'details';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('purchase_invoice_id')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table->striped()
            ->recordTitleAttribute('purchase_invoice_id')
            ->columns([
                // Tables\Columns\TextColumn::make('purchase_invoice_id'),
                Tables\Columns\TextColumn::make('product.name')->label(__('lang.product')),
                Tables\Columns\TextColumn::make('unit.name')->label(__('lang.unit')),
                Tables\Columns\TextColumn::make('quantity')->label(__('lang.quantity'))->alignCenter(true)
                // ->summarize(Sum::make())
                ,
                Tables\Columns\TextColumn::make('package_size')->label(__('lang.package_size'))->alignCenter(true),
                Tables\Columns\TextColumn::make('price')->label(__('lang.price'))->alignCenter(true)
                    ->formatStateUsing(fn($state) => formatMoney($state))
                    ->summarize(Sum::make()->query(function (\Illuminate\Database\Query\Builder $query) {
                        return $query->select('price');
                    })),
                Tables\Columns\TextColumn::make('total_amount')->label(__('lang.total_amount'))->alignCenter(true)
                    ->formatStateUsing(fn($state) => formatMoney($state)),
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
