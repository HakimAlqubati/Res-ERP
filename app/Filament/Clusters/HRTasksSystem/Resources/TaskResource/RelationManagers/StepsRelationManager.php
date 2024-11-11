<?php

namespace App\Filament\Clusters\HRTasksSystem\Resources\TaskResource\RelationManagers;

use App\Models\Task;
use App\Models\TaskLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class StepsRelationManager extends RelationManager
{
    protected static string $relationship = 'steps';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('order')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('order')
            ->columns([
                Tables\Columns\TextColumn::make('order')->sortable(),
                Tables\Columns\TextColumn::make('title')->searchable(),
                // Tables\Columns\ToggleColumn::make('done')
                //     ->disabled(function ($record) {
                //         return false;
                //         if ((isStuff() && ($record?->morphable?->assigned_to == auth()->user()->employee->id)) || isSuperAdmin()) {
                //             return false;
                //         }
                //         return true;

                //     })->action(function($record){
                //         dd('hi');
                //     })
                // ,
                Tables\Columns\IconColumn::make('done')
                ->boolean() // Converts values to boolean, showing one icon for true, another for false
                ->trueIcon('heroicon-o-check-circle') // Icon when true
                ->falseIcon('heroicon-o-x-circle')    // Icon when false
                ->action(function ($record) {
                // Toggle the 'done' state
                // dd($record->morphable);
                if(($record->morphable->task_status == Task::STATUS_NEW || $record->morphable->task_status == Task::STATUS_PENDING) &&  $record->morphable->assign_to == auth()->user()?->employee?->id){
                    $currentStatus = $record->morphable->task_status;
                    $nextStatus = Task::STATUS_IN_PROGRESS;
                    $record->morphable->update(['task_status'=> Task::STATUS_IN_PROGRESS]);
                    $record->morphable->createLog(
                        createdBy: auth()->id(), // ID of the user performing the action
                        description: "Task moved to {$nextStatus}", // Log description
                        logType: TaskLog::TYPE_MOVED, // Log type as "moved"
                        details: [
                            'from' => $currentStatus, // Previous status
                            'to' => $nextStatus, // New status
                        ]
                    );
                }
                $record->update(['done' => 1]);
            }),
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

    protected function canDeleteAny(): bool
    {
        return false;
    }
    protected function canDelete(Model $record): bool
    {
        return false;
    }
    protected function canEdit(Model $record): bool
    {
        return false;
    }
    protected function canCreate(): bool
    {
        return false;
    }
}
