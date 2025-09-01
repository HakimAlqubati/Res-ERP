<?php

namespace App\Filament\Clusters\HRTasksSystem\Resources\TaskLogRelationManagerResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Actions\BulkActionGroup;
use App\Models\Task;
use App\Models\TaskLog;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LogsRelationManager extends RelationManager
{
    protected static string $relationship = 'logs';
    protected static ?string $label = 'Task Log';
    protected static ?string $pluralLabel = 'Task Logs';
    protected static ?string $title = 'Activities';

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
                        $reason = json_decode($record?->details,true) ?? '---';
                        return $reason;
                    }else{
                        return $record->description;
                    }
                })
                ,
            TextColumn::make('total_hours_taken')->alignCenter(true)
            ->sortable()
               ->getStateUsing(function($record){
                
               $details =  json_decode($record->details,true);
               
                if( isset($details['to']) && $record->log_type == TaskLog::TYPE_MOVED && is_array($details)&&  $details['to']== Task::STATUS_CLOSED){
                    return ;
                }
                
                if (
                    in_array($record->log_type,[TaskLog::TYPE_MOVED,TaskLog::TYPE_REJECTED])
                && $record->task->task_status != Task::STATUS_CLOSED
               && $record->task->logs->last()->id == $record->id)
                 {
                    // Get the creation time of the record and the current time
                    $createdAt = $record->created_at; // First date (when the log was created)
                    $now = now(); // Current time
                        // Calculate the time differences
                        $diffInDays = $createdAt->diffInDays($now); // Total days difference
                        $diffInHours = $createdAt->diffInHours($now) % 24; // Hours remaining after full days
                        $diffInMinutes = $createdAt->diffInMinutes($now) % 60; // Minutes remaining after full hours
                        $diffInSeconds = $createdAt->diffInSeconds($now) % 60; // Seconds remaining after full minutes

                        // Format the result
                        if ($diffInDays >= 1) {
                            // If there is at least 1 full day, include days in the output
                            $timeDifference = "{$diffInDays}d {$diffInHours}h {$diffInMinutes}m {$diffInSeconds}s";
                        } elseif ($diffInHours >= 1) {
                            // If there is at least 1 full hour, include hours in the output
                            $timeDifference = "{$diffInHours}h {$diffInMinutes}m {$diffInSeconds}s";
                        } elseif ($diffInMinutes >= 1) {
                            // If there is at least 1 full minute, include minutes in the output
                            $timeDifference = "{$diffInMinutes}m {$diffInSeconds}s";
                        } else {
                            // If less than 1 minute, show only seconds
                            $timeDifference = "{$diffInSeconds}s";
                        }        
                    return $timeDifference;
                }

                  return TaskLog::formatTimeDifferenceFromString($record?->total_hours_taken);
               })
                ->label('Time Spent')
                ,

            TextColumn::make('created_at')
                ->label('Created At')
                ->dateTime()
                ->sortable()
                ,
                IconColumn::make('hasCard')
                ->label('')
                ->getStateUsing(function($record){

                    $rejectionCount = $record->task->rejection_count;

                    if($record->log_type == TaskLog::TYPE_REJECTED){

                        // Check for the yellow card condition
                        if ($rejectionCount >= setting('task_rejection_times_yello_card') 
        && $rejectionCount < setting('task_rejection_times_red_card')) {
        return 'yellow'; // Yellow for the first threshold
    }

    // Check for the red card condition
    if ($rejectionCount == setting('task_rejection_times_red_card')) {
        return 'red'; // Red for the second threshold
    }
    return '';
}
return '';
                    if($record->task->rejection_count == setting('task_rejection_times_yello_card')){
                        return 'yellow';
                    }
                    return 'red';
                })
                ->alignCenter(true)

                ->options([
                    'heroicon-m-credit-card' => 'red',
                    'heroicon-o-credit-card' => 'yellow',
                ])
                ->colors([
                    'secondary',
                    'danger' => 'red',
                    'warning' => 'yellow',
                ])
            
            ])
            ->filters([
              SelectFilter::make('log_type')->label('Activity type')->options([
              TaskLog::TYPE_REJECTED=>TaskLog::TYPE_REJECTED,
              TaskLog::TYPE_MOVED=>TaskLog::TYPE_MOVED,
              ]),
            ])
            ->headerActions([
                // Tables\Actions\CreateAction::make(),
            ])
            ->recordActions([
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
