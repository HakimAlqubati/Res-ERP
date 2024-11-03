<?php

namespace App\Filament\Clusters\HRTasksSystem\Resources\TaskLogRelationManagerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LogsRelationManager extends RelationManager
{
    protected static string $relationship = 'logs';
    protected static ?string $label = 'Task Log';
    protected static ?string $pluralLabel = 'Task Logs';

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
        return $table->striped()
            ->recordTitleAttribute('log_type')
            ->columns([
                TextColumn::make('creator.name')
                ->label('Created By')
                ->sortable(),

            TextColumn::make('log_type')
                ->label('Type')
                ->sortable(),

            TextColumn::make('description')
                ->label('Description')
                ,
            TextColumn::make('total_hours_taken')
                ->label('Total hours taken')
                ,

            TextColumn::make('created_at')
                ->label('Created At')
                ->dateTime()
                ->sortable(),
            
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
