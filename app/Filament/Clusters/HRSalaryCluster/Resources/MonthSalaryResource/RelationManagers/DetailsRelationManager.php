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
        return $table->striped()
            ->recordTitleAttribute('employee_id')
            ->columns([
                Tables\Columns\TextColumn::make('employee.id')->label('ID')->searchable(isIndividual:true)->toggleable(isToggledHiddenByDefault:true)->alignCenter(true),
                Tables\Columns\TextColumn::make('employee.employee_no')->tooltip('Employee number')->label('No.')->searchable(isIndividual:true)->toggleable(isToggledHiddenByDefault:false)->alignCenter(true),
                Tables\Columns\TextColumn::make('employee.name')->tooltip(fn($record):string=>  $record->employee->name)->searchable(isIndividual:true)->limit(15)->alignLeft(true),
                Tables\Columns\TextColumn::make('basic_salary')->sortable()->label('Salary')->alignCenter(true)->numeric(decimalPlaces: 2)->money('MYR'),
                Tables\Columns\TextColumn::make('total_deductions')->sortable()->label('Deducations')->alignCenter(true)->numeric(decimalPlaces: 2) ->money('MYR'),
                Tables\Columns\TextColumn::make('total_allowances')->sortable()->label('Allowances')->alignCenter(true)->numeric(decimalPlaces: 2) ->money('MYR'),
                Tables\Columns\TextColumn::make('total_incentives')->label('Bonus') ->sortable()->sortable()->alignCenter(true)->numeric(decimalPlaces: 2) ->money('MYR'),
                Tables\Columns\TextColumn::make('overtime_pay')->sortable()->label('OV')->alignCenter(true)->alignCenter(true)->numeric(decimalPlaces: 2) ->money('MYR'),
                Tables\Columns\TextColumn::make('total_absent_days')->sortable()->label('Absent days')->alignCenter(true)->alignCenter(true)->numeric(decimalPlaces: 2),
                Tables\Columns\TextColumn::make('net_salary')->sortable()->alignCenter(true)->numeric(decimalPlaces: 2)->money('MYR'),
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
