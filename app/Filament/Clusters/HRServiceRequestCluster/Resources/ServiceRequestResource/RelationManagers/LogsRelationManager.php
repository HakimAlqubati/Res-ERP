<?php

namespace App\Filament\Clusters\HRServiceRequestCluster\Resources\ServiceRequestResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms;
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


    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('log_type')
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
                TextColumn::make('log_type'),
                TextColumn::make('description'),
                TextColumn::make('createdBy.name'),
                TextColumn::make('created_at'),
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
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ]);
    }
}
