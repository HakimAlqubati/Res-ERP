<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources\EwalletPaymentReportResource\RelationManagers;

 
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\TextColumn;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'E-Wallet Verified Sheet';

    // public function form(Form $form): Form
    // {
    //     return $form
    //         ->schema([
    //             // Read-only, no form fields needed usually, unless editing is allowed.
    //         ]);
    // }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('account_number')
            ->columns([
                TextColumn::make('employee.name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('account_number')
                    ->label('Account Number')
                    ->searchable(),
                TextColumn::make('net_salary')
                    ->label('Net Salary (RM)')
                    ->numeric(2)
                    ->sortable(),
                TextColumn::make('reward_name')
                    ->label('Reward Name')
                    ->searchable(),
                TextColumn::make('reward_description')
                    ->label('Reward Description')
                    ->limit(50),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                //
            ]);
    }
}
