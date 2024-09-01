<?php

namespace App\Filament\Clusters\HRTasksSystem\Resources\TaskResource\RelationManagers;

use App\Models\Task;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\CheckboxColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TaskMenuRelationManager extends RelationManager
{
    protected static string $relationship = 'task_menu';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('id')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            // ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('menuTask.name'),
                CheckboxColumn::make('done')->disabled(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Action::make('done')->action(function ($record) {
                    if ($record->done == 0) {
                        return $record->update([
                            'done' => 1
                        ]);
                    } else {
                        return $record->update([
                            'done' => 0
                        ]);
                    }
                })->disabled(function ($record) {
                    $task = $record->task;
                    if ($task->task_status != Task::STATUS_IN_PROGRESS) {
                        return true;
                    }
                })
                    ->requiresConfirmation()
                    ->button()
                    ->label(function ($record) {
                        if ($record->done == 0) {
                            return 'Check if done';
                        } else {
                            return 'Uncheck to undone';
                        }
                    })
                    ->icon(function ($record) {
                        if ($record->done == 0) {
                            return 'heroicon-m-check';
                        } else {
                            return 'heroicon-m-x-mark';
                        }
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected function canCreate(): bool
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
}
