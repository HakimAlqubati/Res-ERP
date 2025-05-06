<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentMethodResource\Pages;
use App\Filament\Resources\PaymentMethodResource\RelationManagers;
use App\Models\PaymentMethod;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaymentMethodResource extends Resource
{
    protected static ?string $model = PaymentMethod::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $modelLabel = 'Payment Method';
    protected static ?string $pluralModelLabel = 'Payment Methods';
    protected static ?string $slug = 'payment-methods';
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make()->label('')->schema([
                    Grid::make()->columns(3)->schema([
                        TextInput::make('name')
                            ->label('Name')
                            ->required()->columnSpan(2)
                            ->maxLength(100),
                        Toggle::make('is_active')
                            ->label('Active')->inline(false)
                            ->default(true),

                    ]),
                    Textarea::make('description')
                        ->label('Description')
                        ->rows(3)->columnSpanFull()
                        ->maxLength(255),

                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table->striped()
            ->columns([
                TextColumn::make('id')
                    ->label('ID')->alignCenter(true)
                    ->sortable()->toggleable()
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Name')
                    ->sortable()
                    ->searchable()->toggleable(),

                TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)->toggleable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()->alignCenter(true),

            ])
            ->filters([
                //
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentMethods::route('/'),
            'create' => Pages\CreatePaymentMethod::route('/create'),
            'edit' => Pages\EditPaymentMethod::route('/{record}/edit'),
        ];
    }
}
