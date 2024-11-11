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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        return $table->striped()
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
                    ->falseIcon('heroicon-o-x-circle') // Icon when false
                    ->action(function ($record) {
                        // Use a database transaction for safe execution
                        DB::beginTransaction();

                        try {
                            // Check the conditions before updating the status
                            if (($record->morphable->task_status == Task::STATUS_NEW || $record->morphable->task_status == Task::STATUS_PENDING)
                                && $record->morphable->assign_to == auth()->user()?->employee?->id) {

                                $currentStatus = $record->morphable->task_status;
                                $nextStatus = Task::STATUS_IN_PROGRESS;

                                // Update task status
                                $record->morphable->update(['task_status' => $nextStatus]);

                                // Log the status change
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

                            // Toggle the 'done' status
                            $record->update(['done' => $record->done == 1 ? 0 : 1]);

                            // Commit the transaction
                            DB::commit();

                            // Return success message
                            \Filament\Notifications\Notification::make()
                                ->title('Success')
                                ->body('Done')
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            // Rollback the transaction in case of an error
                            DB::rollBack();

                            // Log the error for debugging
                            Log::error("Error updating task status: " . $e->getMessage());

                            // Return error message
                            \Filament\Notifications\Notification::make()
                                ->title('Error')
                                ->body('Failed. Please try again later.')
                                ->danger()
                                ->send();
                        }
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
