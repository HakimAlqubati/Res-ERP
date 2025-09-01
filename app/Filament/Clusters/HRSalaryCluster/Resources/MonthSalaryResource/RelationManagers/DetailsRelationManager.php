<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources\MonthSalaryResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Support\Enums\TextSize;
use Filament\Actions\BulkActionGroup;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;

class DetailsRelationManager extends RelationManager
{
    protected static string $relationship = 'details';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                TextColumn::make('employee.id')->label('ID')->searchable(isIndividual:true)->toggleable(isToggledHiddenByDefault:true)->alignCenter(true),
                TextColumn::make('employee.employee_no')->tooltip('Employee number')->label('No.')->searchable(isIndividual:true)->toggleable(isToggledHiddenByDefault:false)->alignCenter(true)
                ->icon('heroicon-m-identification')


                ,
                TextColumn::make('employee.name')->tooltip(fn($record):string=>  $record->employee->name)
                ->searchable(isIndividual:true)
                // ->limit(15)
                // ->lineClamp(2)
                ->wrap()
                ->alignLeft(true)
                ->fontFamily(FontFamily::Mono)
                ->size(TextSize::Large)
                ,
                TextColumn::make('basic_salary')->sortable()->label('Salary')->alignCenter(true)->numeric(decimalPlaces: 2),
                TextColumn::make('total_deductions')->sortable()->label('Deducations')->alignCenter(true)->numeric(decimalPlaces: 2) ,
                TextColumn::make('total_allowances')->sortable()->label('Allowances')->alignCenter(true)->numeric(decimalPlaces: 2) ,
                TextColumn::make('total_incentives')->label('Bonus') ->sortable()->sortable()->alignCenter(true)->numeric(decimalPlaces: 2) ,
                TextColumn::make('overtime_pay')->sortable()->label('OV')->alignCenter(true)->alignCenter(true)->numeric(decimalPlaces: 2) ,
                TextColumn::make('total_absent_days')->sortable()->label('Absent days')->alignCenter(true)->alignCenter(true),
                TextColumn::make('net_salary')->sortable()->alignCenter(true)->numeric(decimalPlaces: 2)
                ->money('MYR')->color('success')
                ->weight(FontWeight::Bold)
                ,
            ])
            ->filters([
                //
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
