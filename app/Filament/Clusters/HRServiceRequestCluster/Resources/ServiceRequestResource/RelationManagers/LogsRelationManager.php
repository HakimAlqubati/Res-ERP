<?php

namespace App\Filament\Clusters\HRServiceRequestCluster\Resources\ServiceRequestResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LogsRelationManager extends RelationManager
{
    protected static string $relationship = 'logs';
    protected static ?string $label = 'Activity Logs';
    protected static ?string $pluralLabel = 'Activity Logs';
    
    protected static ?string $title = 'Activities';

    // public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    // {return $ownerRecord->logs->count();}

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {return $ownerRecord->logs->count();}


    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('log_type')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('log_type')
            ->striped()
            ->columns([
                Tables\Columns\TextColumn::make('log_type'),
                Tables\Columns\TextColumn::make('description'),
                Tables\Columns\TextColumn::make('createdBy.name'),
                Tables\Columns\TextColumn::make('created_at'),
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
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ]);
    }
}
