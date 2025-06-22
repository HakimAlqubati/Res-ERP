<?php

namespace App\Filament\Clusters\ResellersCluster\Resources\DeliveredResellerOrdersResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'paidAmounts';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make()->columns(2)->schema([
                    TextInput::make('amount')
                        ->label('Amount')
                        ->required()
                        ->numeric()
                        ->minValue(0.01)
                        ->prefixIcon('heroicon-o-banknotes'),
                    DatePicker::make('paid_at')
                        ->label('Paid At')
                        ->prefixIcon('heroicon-o-calendar-days')
                        ->default(now())
                        ->required(),
                ]),
                Textarea::make('notes')
                    ->label('Notes')
                    ->rows(3)
                    ->maxLength(500)->columnSpanFull()
                    ->nullable(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('order_id')
            ->columns([
                TextColumn::make('amount')
                    ->label('Amount')
                    ->money()
                    ->sortable(),

                TextColumn::make('paid_at')
                    ->label('Paid At')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('notes')
                    ->label('Notes')
                    ->limit(40)
                    ->wrap(),

                TextColumn::make('creator.name')
                    ->label('Created By')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
