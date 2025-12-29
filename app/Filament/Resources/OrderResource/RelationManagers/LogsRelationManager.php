<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use Filament\Tables\Columns\TextColumn;
use Carbon\Carbon;
use App\Models\OrderLog;
use Filament\Forms;
use Filament\Tables;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LogsRelationManager extends RelationManager
{
    protected static string $relationship = 'logs';

    protected static ?string $title = 'Order Logs';



    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'asc')
            ->columns([
                TextColumn::make('log_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        OrderLog::TYPE_CREATED => 'success',
                        OrderLog::TYPE_UPDATED => 'warning',
                        OrderLog::TYPE_CHANGE_STATUS => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('message')
                    ->label('Notes')
                    ->wrap(),

                TextColumn::make('new_status')
                    ->label('New Status')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                TextColumn::make('creator.name')
                    ->label('Created By'),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->formatStateUsing(fn($state) => Carbon::parse($state)->format('d-m-Y h:i A'))

                    // ->since()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'asc')
            ->filters([])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
    public static function modifyQueryUsing(Builder $query): Builder
    {
        return $query->where(function ($query) {
            $query->where('log_type', '!=', OrderLog::TYPE_UPDATED)
                ->orWhere(function ($query) {
                    $query->where('log_type', OrderLog::TYPE_UPDATED)
                        ->whereRaw("message NOT LIKE '%Updated fields%'");
                });
        });
    }

     public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return $ownerRecord->logs->count();
    }
}