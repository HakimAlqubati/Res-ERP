<?php
namespace App\Filament\Clusters\HRCluster\Resources\EmployeeResource\RelationManagers;

use App\Enums\DayOfWeek;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\Concerns\CanCustomizeProcess;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Fieldset;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class PeriodHistoriesRelationManager extends RelationManager
{
    use CanCustomizeProcess;
    protected static string $relationship = 'periodHistories';
    protected static ?string $title       = 'Shift History';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('period_id', 'asc')
            ->striped()
            ->recordTitleAttribute('period_id')
            ->columns([
                TextColumn::make('workPeriod.id')->label('Shift id')->alignCenter(true)->sortable(),
                TextColumn::make('workPeriod.name')->label('Shift name'),
                TextColumn::make('start_date')->label('Start date')->sortable(),
                TextColumn::make('end_date')->label('End date')->default('Current date'),
                TextColumn::make('day_of_week')
                    ->label('Day'),

                // TextColumn::make('creator.name')->label('Created by'),
                // TextColumn::make('start_time')->label('Start time'),
                // TextColumn::make('end_time')->label('End time'),

            ])
            ->filters([
                Tables\Filters\SelectFilter::make('day_of_week')
                    ->options(DayOfWeek::options())
                    ->label('Filter by Day'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                Action::make('edit')->label('Edit')->button()

                    ->action(function ($record, $data): void {
                        $recordMonth = date('m', strtotime($record->start_date));
                        $recordYear  = date('Y', strtotime($record->start_date));

                        $endOfMonth                 = getEndOfMonthDate($recordYear, $recordMonth);
                        $isSalaryCreatedForEmployee = isSalaryCreatedForEmployee($record->employee_id, $endOfMonth['start_month'], $endOfMonth['end_month']);
                        if ($isSalaryCreatedForEmployee) {
                            Notification::make()->title('Cannot edit because salary is already created')->warning()->send();
                            return;
                        }

                        DB::beginTransaction();
                        try {
                            $record->update($data);

                            DB::commit();
                            Notification::make()->title('Updated successfully!')->success()->send();
                        } catch (\Throwable $e) {
                            DB::rollBack();
                            Notification::make()
                                ->title('Error')
                                ->body('An error occurred while saving: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })

                    ->form(fn($record) => [
                        Fieldset::make()->columns(3)->schema([
                            DatePicker::make('start_date')
                                ->default($record->start_date)
                                ->required(),
                            DatePicker::make('end_date')
                                ->default($record->end_date),
                            Select::make('day_of_week')
                                ->label('Day of Week')
                                ->options(DayOfWeek::options())
                                ->default(is_object($record->day_of_week) ? $record->day_of_week->value : $record->day_of_week)
                                ->required(),
                        ]),
                    ])

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