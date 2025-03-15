<?php

namespace App\Filament\Clusters\HRCluster\Resources\EmployeeResource\RelationManagers;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PeriodHistoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'periodHistories';
    protected static ?string $title = 'Shift History';
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('start_date')
                ->minDate(function($record){
                    return $record->employee->join_date?? now()->toDateString();
                })->required()
                ,
                DatePicker::make('end_date'),
                CheckboxList::make('days')
                ->label('Days of Work')
                ->columns(3)
                ->options(getDays())
                ->required()->columnSpanFull()
                ->bulkToggleable()
                ->helperText('Select the days this period applies to.'),
                // TimePicker::make('start_time'),
                // TimePicker::make('end_time'),
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
                TextColumn::make('start_date')->label('Start date')->sortable(),
                TextColumn::make('end_date')->label('End date')->default('Current date'),
                TextColumn::make('days')->label('Days')->formatStateUsing(fn ($state) => implode(', ', json_decode($state, true) ?? [])),

                // TextColumn::make('creator.name')->label('Created by'),
                // TextColumn::make('start_time')->label('Start time'),
                // TextColumn::make('end_time')->label('End time'),

            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->visible(fn(): bool => (isSuperAdmin() || isBranchManager())),
                Action::make('disable')->label('Disable')->visible(fn($record): bool => (isSuperAdmin() && $record->active == 1))
                    ->requiresConfirmation()->databaseTransaction()
                   ->button()
                    ->color(Color::Red)
                    ->action(function ($record) {
                        $record->update(['active' => 0]);
                        Notification::make()->title('Done')->send();
                    }),
                Action::make('enable')->label('Enable')->visible(fn($record): bool => (isSuperAdmin() && $record->active == 0))
                    ->requiresConfirmation()->databaseTransaction()
                    ->button()
                    ->color(Color::Green)
                    ->action(function ($record) {
                        $record->update(['active' => 1]);
                        Notification::make()->title('Done')->send();
                    }),
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
