<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OcrResource\Pages;
use App\Filament\Resources\OcrResource\RelationManagers;
use App\Models\Ocr;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OcrResource extends Resource
{
    protected static ?string $model = Ocr::class;
    protected static ?string $slug = 'test-ocr';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\FileUpload::make('image_path')
                    ->label('رفع صورة لاستخراج النص')
                    ->image()->columnSpanFull()
                    ->directory('ocr-uploads')
                    ->required(),

                Forms\Components\Textarea::make('extracted_text')
                    ->label('النص المستخرج')
                    ->disabled()->columnSpanFull()
                    ->dehydrated()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_path')
                    ->label('Image'),
                Tables\Columns\TextColumn::make('extracted_text')
                    ->label('Text Extracted')
                    ->limit(50)->tooltip(fn($record) => $record->extracted_text),
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
            'index' => Pages\ListOcrs::route('/'),
            'create' => Pages\CreateOcr::route('/create'),
            'edit' => Pages\EditOcr::route('/{record}/edit'),
        ];
    }
}
