<?php

declare(strict_types=1);

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\HolidayOccurrences\Actions;

use App\Models\HolidayOccurrence;
use App\Modules\HR\Holidays\Builders\HolidayOccurrencePayloadBuilder;
use App\Modules\HR\Holidays\Contracts\HolidayOccurrenceServiceInterface;
use App\Modules\HR\Holidays\DTOs\ConfirmHolidayOccurrenceDTO;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

final class HolidayOccurrenceTableActions
{
    public static function generateForYearAction(): Action
    {
        return Action::make('generateForYear')
            ->label('Generate Year Holidays')
            ->icon('heroicon-o-sparkles')
            ->color('primary')
            ->schema([
                Select::make('year')
                    ->label('Target Gregorian Year')
                    ->options(HolidayOccurrence::yearOptions())
                    ->default((int) now()->year)
                    ->required(),
            ])
            ->action(function (array $data): void {
                $year = (int) $data['year'];
                $createdCount = app(HolidayOccurrenceServiceInterface::class)
                    ->generateForYear($year, auth()->id());

                Notification::make()
                    ->title($createdCount > 0
                        ? "Generated {$createdCount} holiday occurrence(s) for {$year}."
                        : "All active holiday templates are already generated for {$year}.")
                    ->success()
                    ->send();
            });
    }

    public static function confirmDatesAction(): Action
    {
        return Action::make('confirmDates')
            ->label('Confirm Dates')
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->visible(fn (HolidayOccurrence $record): bool => $record->isTentative())
            ->fillForm(fn (HolidayOccurrence $record): array => [
                'from_date'  => $record->from_date?->toDateString() ?? now()->toDateString(),
                'to_date'    => $record->to_date?->toDateString() ?? now()->toDateString(),
                'count_days' => $record->count_days ?: 1,
            ])
            ->schema([
                Fieldset::make('Review & Confirm Actual Official Dates')
                    ->columns(3)
                    ->schema([
                        DatePicker::make('from_date')
                            ->label('Actual From Date')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (?string $state, Get $get, Set $set): void {
                                if (! $state) {
                                    return;
                                }
                                $calc = HolidayOccurrencePayloadBuilder::calculateFromStartAndDays(
                                    $state,
                                    max(1, (int) ($get('count_days') ?: 1))
                                );
                                $set('to_date', $calc['to_date']);
                            }),

                        TextInput::make('count_days')
                            ->label('Number of Days')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(60)
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Get $get, Set $set): void {
                                $fromDate = (string) ($get('from_date') ?? '');
                                if ($fromDate === '') {
                                    return;
                                }
                                $calc = HolidayOccurrencePayloadBuilder::calculateFromStartAndDays(
                                    $fromDate,
                                    max(1, (int) ($state ?: 1))
                                );
                                $set('to_date', $calc['to_date']);
                            }),

                        DatePicker::make('to_date')
                            ->label('Actual To Date')
                            ->required()
                            ->afterOrEqual('from_date')
                            ->live()
                            ->afterStateUpdated(function (?string $state, Get $get, Set $set): void {
                                $fromDate = (string) ($get('from_date') ?? '');
                                if ($fromDate === '' || ! $state) {
                                    return;
                                }
                                $calc = HolidayOccurrencePayloadBuilder::calculateFromDateRange($fromDate, $state);
                                $set('count_days', $calc['count_days']);
                            }),
                    ]),
            ])
            ->action(function (HolidayOccurrence $record, array $data): void {
                app(HolidayOccurrenceServiceInterface::class)->confirm(
                    $record,
                    ConfirmHolidayOccurrenceDTO::fromArray($data, auth()->id())
                );

                Notification::make()
                    ->title('Holiday dates confirmed successfully.')
                    ->success()
                    ->send();
            });
    }

    public static function shiftDatesAction(): Action
    {
        return Action::make('shiftDates')
            ->label('Shift ± Days')
            ->icon('heroicon-o-arrows-right-left')
            ->color('warning')
            ->schema([
                Select::make('days_offset')
                    ->label('Shift Direction & Days')
                    ->options([
                        -2 => '-2 Days (Earlier)',
                        -1 => '-1 Day (Earlier)',
                        1  => '+1 Day (Later)',
                        2  => '+2 Days (Later)',
                    ])
                    ->default(1)
                    ->required(),
            ])
            ->action(function (HolidayOccurrence $record, array $data): void {
                app(HolidayOccurrenceServiceInterface::class)->shiftDays(
                    $record,
                    (int) $data['days_offset'],
                    auth()->id()
                );

                Notification::make()
                    ->title('Holiday dates shifted successfully.')
                    ->success()
                    ->send();
            });
    }
}
