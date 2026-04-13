<?php

namespace App\Filament\Clusters\HRCluster\Resources\EmployeeResource\RelationManagers;

use App\Enums\DayOfWeek;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\Concerns\CanCustomizeProcess;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Fieldset;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PeriodHistoriesRelationManager extends RelationManager
{
    use CanCustomizeProcess;
    protected static string $relationship = 'periodHistories';
    protected static ?string $title       = 'Shift History';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        // مثال: عدد الشفتات لهذا الموظف
        return $ownerRecord->periodHistories()->count();
    }

    public static function getBadgeColor(Model $ownerRecord, string $pageClass): ?string
    {
        // تقدر ترجع لون حسب الحالة
        $count = $ownerRecord->periodHistories()->count();

        if ($count === 0) {
            return 'gray';
        }

        if ($count < 3) {
            return 'warning';
        }

        return 'success';
    }

    public static function getBadgeTooltip(Model $ownerRecord, string $pageClass): ?string
    {
        return "Shift Histories Count: " . $ownerRecord->periodHistories()->count();
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('start_date', 'desc')
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
                TextColumn::make('start_time')->label('Start time'),
                TextColumn::make('end_time')->label('End time'),

            ])
            ->filters([
                Tables\Filters\SelectFilter::make('day_of_week')
                    ->options(DayOfWeek::options())
                    ->label('Filter by Day'),
            ])
            ->headerActions([
                // CreateAction::make(),
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
                            TimePicker::make('start_time')
                                ->default($record->start_time)
                                ->visible(fn() => isHakimOrAdel())
                                ->required(),
                            TimePicker::make('end_time')
                                ->default($record->end_time)
                                ->visible(fn() => isHakimOrAdel())
                                ->required(),
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
                    BulkAction::make('bulkEditDates')
                        ->label('Edit Dates')
                        ->icon('heroicon-o-calendar-days')
                        ->color(Color::Blue)
                        ->visible(fn(): bool => isSuperAdmin() || isBranchManager())
                        ->form([
                            DatePicker::make('start_date')
                                ->label('Start Date')
                                ->required(),
                            DatePicker::make('end_date')
                                ->label('End Date'),
                        ])
                        ->before(function (BulkAction $action, \Illuminate\Database\Eloquent\Collection $records) {
                            // ── التحقق 1: كل السجلات يجب أن تملك نفس period_id ──
                            $uniquePeriods = $records->pluck('period_id')->unique();
                            if ($uniquePeriods->count() > 1) {
                                Notification::make()
                                    ->title('Invalid Selection')
                                    ->body('All selected records must belong to the same shift (period). Found: ' . $uniquePeriods->implode(', '))
                                    ->danger()
                                    ->send();
                                $action->cancel();
                                return;
                            }

                            // ── التحقق 2: كل السجلات يجب أن تكون في نفس الفترة (نفس start_date month/year) ──
                            $uniqueMonths = $records->map(function ($record) {
                                return \Carbon\Carbon::parse($record->start_date)->format('Y-m');
                            })->unique();

                            if ($uniqueMonths->count() > 1) {
                                Notification::make()
                                    ->title('Invalid Selection')
                                    ->body('All selected records must be in the same month/period. Found months: ' . $uniqueMonths->implode(', '))
                                    ->danger()
                                    ->send();
                                $action->cancel();
                                return;
                            }
                        })
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data): void {
                            DB::beginTransaction();
                            try {
                                foreach ($records as $record) {
                                    $updateData = array_filter([
                                        'start_date' => $data['start_date'] ?? null,
                                        'end_date'   => $data['end_date'] ?? null,
                                    ], fn($v) => !is_null($v));

                                    $record->update($updateData);
                                }

                                DB::commit();
                                Notification::make()
                                    ->title('Updated successfully!')
                                    ->body('Updated ' . $records->count() . ' records.')
                                    ->success()
                                    ->send();
                            } catch (\Throwable $e) {
                                DB::rollBack();
                                Notification::make()
                                    ->title('Error')
                                    ->body('An error occurred: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                    // Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
    protected function canCreate(): bool
    {
        return false;
    }
}
