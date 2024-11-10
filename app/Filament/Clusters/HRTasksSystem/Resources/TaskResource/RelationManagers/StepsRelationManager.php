<?php

namespace App\Filament\Clusters\HRTasksSystem\Resources\TaskResource\RelationManagers;

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
                Tables\Columns\ToggleColumn::make('done')
                    ->disabled(function ($record) {
                        return false;
                        if ((isStuff() && ($record?->morphable?->assigned_to == auth()->user()->employee->id)) || isSuperAdmin()) {
                            return false;
                        }
                        return true;

                    })
                ,
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
