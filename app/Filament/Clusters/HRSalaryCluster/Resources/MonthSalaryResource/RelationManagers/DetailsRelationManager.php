<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources\MonthSalaryResource\RelationManagers;

use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class DetailsRelationManager extends RelationManager
{
    protected static string $relationship = 'details';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Forms\Components\TextInput::make('employee_id')
                //     ->required()
                //     ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('employee_id')
            ->columns([
                Tables\Columns\TextColumn::make('employee.name')->searchable(isIndividual:true),
                Tables\Columns\TextColumn::make('basic_salary')->sortable(),
                Tables\Columns\TextColumn::make('total_deductions')->sortable(),
                Tables\Columns\TextColumn::make('total_allowances')->sortable(),
                Tables\Columns\TextColumn::make('total_incentives')->label('Total bonus') ->sortable(),
                Tables\Columns\TextColumn::make('overtime_pay')->sortable(),
                Tables\Columns\TextColumn::make('total_absent_days')->sortable(),
                Tables\Columns\TextColumn::make('net_salary')->sortable(),
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
