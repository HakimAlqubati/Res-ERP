<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources\EwalletPaymentReportResource\RelationManagers;

 
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\TextColumn;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'eWallet Verified Sheet';

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
        ->defaultSort('id','desc')
            ->recordTitleAttribute('account_number')
            ->columns([
                TextColumn::make('employee.name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ,
                TextColumn::make('account_number')
                    ->label('Account Number')
                    ->searchable()
                    ->toggleable()
                    ,
                TextColumn::make('net_salary')
                    ->label('Net Salary')
                    ->formatStateUsing(fn($state)=>formatMoneyWithCurrency($state))
                    ->sortable()
                    ->toggleable()
                    ->summarize(Sum::make()->formatStateUsing(fn($state)=>formatMoneyWithCurrency($state)))
                    ,
                TextColumn::make('reward_name')
                    ->label('Reward Name')
                    ->searchable()
                    ->toggleable()
                    ,
                TextColumn::make('reward_description')
                    ->label('Reward Description')
                    ->limit(50)
                    ->tooltip(fn($state) => $state)
                    ->toggleable()
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
