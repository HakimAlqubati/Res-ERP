<?php

namespace App\Filament\Clusters\HRCluster\Resources\EmployeeResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PeriodHistoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'periodHistories';
    protected static ?string $title = 'Shift histories';
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('period_id')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('start_date', 'asc')
            ->striped()
            ->recordTitleAttribute('period_id')
            ->columns([
                TextColumn::make('workPeriod.id')->label('Shift id')->alignCenter(true)->sortable(),
                TextColumn::make('workPeriod.name')->label('Shift name'),
                TextColumn::make('start_date')->label('Start Date')->sortable(),
                TextColumn::make('end_date')->label('End Date')->default('At now'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
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
    protected function canCreate(): bool
    {
        return false;
    }
}
