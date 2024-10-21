<?php

namespace App\Filament\Clusters\HRCluster\Resources\EmployeeResource\RelationManagers;

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
    protected static ?string $title = 'Shift history';
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('end_date'),
                TimePicker::make('start_time'),
                TimePicker::make('end_time'),
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
                TextColumn::make('end_date')->label('End date')->default('At now'),
                TextColumn::make('start_time')->label('Start time'),
                TextColumn::make('end_time')->label('End time'),

            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                // Tables\Actions\EditAction::make()->visible(fn(): bool => isSuperAdmin()),
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
