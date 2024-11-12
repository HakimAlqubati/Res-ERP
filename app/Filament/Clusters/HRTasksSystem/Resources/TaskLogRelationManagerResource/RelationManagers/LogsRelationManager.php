<?php

namespace App\Filament\Clusters\HRTasksSystem\Resources\TaskLogRelationManagerResource\RelationManagers;

use App\Models\TaskLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LogsRelationManager extends RelationManager
{
    protected static string $relationship = 'logs';
    protected static ?string $label = 'Task Log';
    protected static ?string $pluralLabel = 'Task Logs';
    protected static ?string $title = 'Activities';

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
                ->getStateUsing(function($record){
                    if($record->log_type== TaskLog::TYPE_REJECTED){
                        $reason = json_decode($record?->details,true)?? '---';
                        return $reason;
                        return 'ddd';
                    }else{
                        return $record->description;
                    }
                })
                ,
            TextColumn::make('total_hours_taken')->alignCenter(true)
            ->sortable()
               ->getStateUsing(function($record){
                if($record->log_type != TaskLog::TYPE_MOVED){
                    return '-';
                }
                //  dd( $record->created_at, now());
                if(count($record->task->logs)==1 && $record->task->logs->first()->id == $record->id){
                    // Assuming $createdAt and $now are your Carbon instances
                    $createdAt = $record->created_at; // First date
                    $now = now(); // Current time

                    // Calculate the time differences
                    $diffInDays = $createdAt->diffInDays($now); // Total days difference
                    $diffInHours = $createdAt->diffInHours($now) % 24; // Hours remaining after full days
                    $diffInMinutes = $createdAt->diffInMinutes($now) % 60; // Minutes remaining after full hours

                    // Format the result
                    if ($diffInDays >= 1) {
                        // If there is at least 1 full day, include days in the output
                        $timeDifference = "{$diffInDays}d {$diffInHours}h {$diffInMinutes}m";
                    } else {
                        // If less than 1 day, only show hours and minutes
                        $timeDifference = "{$diffInHours}h {$diffInMinutes}m";
                    }
                    return $timeDifference;
                  }
                  return TaskLog::formatTimeDifferenceFromString($record?->total_hours_taken);
               })
                ->label('Total hours taken')
                ,

            TextColumn::make('created_at')
                ->label('Created At')
                ->dateTime()
                ->sortable(),
            
            ])
            ->filters([
              SelectFilter::make('log_type')->label('Activity type')->options([
              TaskLog::TYPE_REJECTED,
              TaskLog::TYPE_MOVED,
              ]),
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
