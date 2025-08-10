<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources\PayrollResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('type')->label('Type')->options([
                    'salary' => 'Salary',
                    'allowance' => 'Allowance',
                    'deduction' => 'Deduction',
                    'advance' => 'Advance',
                    'installment' => 'Installment',
                    'bonus' => 'Bonus',
                    'overtime' => 'Overtime',
                    'penalty' => 'Penalty',
                    'other' => 'Other',
                    'net_salary' => 'Net salary',
                ])->required(),
                Forms\Components\TextInput::make('sub_type')->label('Sub type')->maxLength(50),
                Forms\Components\TextInput::make('amount')->numeric()->required(),
                Forms\Components\TextInput::make('currency')->default('YER')->maxLength(6),
                Forms\Components\Textarea::make('description')->maxLength(255),
                Forms\Components\DatePicker::make('date')->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('employee')
            ->columns([
                Tables\Columns\TextColumn::make('date')->date(),
                Tables\Columns\BadgeColumn::make('type'),
                Tables\Columns\TextColumn::make('sub_type')->label('Sub type')->toggleable(),
                Tables\Columns\TextColumn::make('amount')->money('YER', true),
                Tables\Columns\TextColumn::make('description')->wrap()->toggleable(),
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
}
