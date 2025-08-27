<?php

namespace App\Filament\Resources\PurchaseInvoiceResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Actions\CreateAction;
use App\Models\PurchaseInvoice;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PurchaseInvoiceDetailsRelationManager extends RelationManager
{
    

    protected static ?string $recordTitleAttribute = 'purchase_invoice_id';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('purchase_invoice_id')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    // public static function getTitle(Model $ownerRecord, string $pageClass): string
    // {
    //     return __('lang.purchase_invoice_details');
    // }
    public function table(Table $table): Table
    {
        return $table->striped() 
            ->columns([
                TextColumn::make('id'),
                // Tables\Columns\TextColumn::make('product.name')->label(__('lang.product')),
                // Tables\Columns\TextColumn::make('unit.name')->label(__('lang.unit')),
                // Tables\Columns\TextColumn::make('quantity')->label(__('lang.quantity')),
                // Tables\Columns\TextColumn::make('package_size')->label(__('lang.package_size')),
                // Tables\Columns\TextColumn::make('price')->label(__('lang.price')),
                // Tables\Columns\TextColumn::make('total_amount')->label(__('lang.total_amount')),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                // Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    protected function canCreate(): bool
    {
        return false;
    }

    protected function isTablePaginationEnabled(): bool
    {
        return false;
    }
}
