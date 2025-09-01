<?php

namespace App\Filament\Clusters\SupplierCluster\Resources\PurchaseInvoiceResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\BulkActionGroup;
use App\Models\PurchaseInvoice;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DetailsRelationManager extends RelationManager
{
    protected static string $relationship = 'details';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('purchase_invoice_id')
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
                TextColumn::make('product.name')->label(__('lang.product')),
                TextColumn::make('unit.name')->label(__('lang.unit')),
                TextColumn::make('quantity')->label(__('lang.quantity'))->alignCenter(true)
                // ->summarize(Sum::make())
                ,
                TextColumn::make('package_size')->label(__('lang.package_size'))->alignCenter(true),
                TextColumn::make('price')->label(__('lang.price'))->alignCenter(true)
                    ->hidden(fn(): bool => isStoreManager())
                    ->formatStateUsing(fn($state) => formatMoney($state))
                    ->summarize(Sum::make()->query(function (\Illuminate\Database\Query\Builder $query) {
                        return $query->select('price');
                    })),
                TextColumn::make('unit_total_price')->label(__('lang.total_amount'))->alignCenter(true)
                    ->hidden(fn(): bool => isStoreManager())
                    ->summarize(Sum::make())
                    ->formatStateUsing(fn($state) => formatMoney($state)),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Tables\Actions\CreateAction::make(),
            ])
            ->recordActions([
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),

            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
