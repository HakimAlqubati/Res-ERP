<?php

namespace App\Filament\Resources\PurchaseInvoiceResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;

use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PurchaseInvoiceDetailsRelationManager extends RelationManager
{
    protected static string $relationship = 'purchaseInvoiceDetails';

    protected static ?string $recordTitleAttribute = 'purchase_invoice_id';

    // public static function form(Form $form): Form
    // {
    //     return $form
    //         ->schema([
    //             Forms\Components\TextInput::make('purchase_invoice_id')
    //                 ->required()
    //                 ->maxLength(255),
    //         ]);
    // }

    // public static function getTitle(): string
    // {
    //     return __('lang.purchase_invoice_details');
    // }
    // public static function table(Table $table): Table
    // {
    //     return $table
    //         ->columns([
    //             Tables\Columns\TextColumn::make('product.name')->label(__('lang.product')),
    //             Tables\Columns\TextColumn::make('unit.name')->label(__('lang.unit')),
    //             Tables\Columns\TextColumn::make('quantity')->label(__('lang.quantity')),
    //             Tables\Columns\TextColumn::make('price')->label(__('lang.price')),
    //             Tables\Columns\TextColumn::make('total_amount')->label(__('lang.total_amount')),
    //         ])
    //         ->filters([
    //             //
    //         ])
    //         ->headerActions([
    //             // Tables\Actions\CreateAction::make(),
    //         ])
    //         ->actions([
    //             // Tables\Actions\EditAction::make(),
    //             // Tables\Actions\DeleteAction::make(),
    //         ])
    //         ->bulkActions([
    //             // Tables\Actions\DeleteBulkAction::make(),
    //         ]);
    // }

    protected function canCreate(): bool
    {
        return false;
    }
}
