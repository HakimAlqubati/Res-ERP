<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use App\Models\OrderLog;
use Filament\Forms;
use Filament\Tables;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LogsRelationManager extends RelationManager
{
    protected static string $relationship = 'logs';

    protected static ?string $title = 'Order Logs';



    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('log_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        OrderLog::TYPE_CREATED => 'success',
                        OrderLog::TYPE_UPDATED => 'warning',
                        OrderLog::TYPE_CHANGE_STATUS => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('message')
                    ->label('Notes')
                    ->limit(50),

                Tables\Columns\TextColumn::make('new_status')
                    ->label('New Status')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Created By'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
