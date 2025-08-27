<?php

namespace App\Filament\Clusters\HRCluster\Resources\EmployeeResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Arr;
use Filament\Actions\Concerns\CanCustomizeProcess;

class PeriodHistoriesRelationManager extends RelationManager
{
    use CanCustomizeProcess;
    protected static string $relationship = 'periodHistories';
    protected static ?string $title = 'Shift History';
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('start_date')
                    // ->minDate(function($record){
                    //     return $record->employee->join_date?? now()->toDateString();
                    // })
                    ->required(),
                DatePicker::make('end_date'),
                CheckboxList::make('period_days')
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
                TextColumn::make('period_days_val')->label('Days'),

                // TextColumn::make('creator.name')->label('Created by'),
                // TextColumn::make('start_time')->label('Start time'),
                // TextColumn::make('end_time')->label('End time'),

            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make()
                    ->action(function ($record, $data): void {
                        $recordMonth = date('m', strtotime($record->start_date));
                        $recordYear = date('Y', strtotime($record->start_date));

                        $endOfMonth = getEndOfMonthDate($recordYear, $recordMonth);
                        $isSalaryCreatedForEmployee = isSalaryCreatedForEmployee($record->employee_id, $endOfMonth['start_month'], $endOfMonth['end_month']);
                        // dd($isSalaryCreatedForEmployee,$endOfMonth);
                        if ($isSalaryCreatedForEmployee) {
                            Notification::make()->title('Cannot edit because salary is already created')->warning()->send();
                            return;
                        }
                        $record->update($data);
                    })
                    ->visible(fn(): bool => (isSuperAdmin() || isBranchManager())),
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
            ->toolbarActions([
                BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
    protected function canCreate(): bool
    {
        return false;
    }
}
