<?php

declare(strict_types=1);

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\HolidayTemplates\Schemas;

use App\Models\Branch;
use App\Models\HolidayTemplate;
use Carbon\Carbon;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class HolidayTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        $syncDatesFromFixedDayMonth = static function (Get $get, Set $set): void {
            if ($get('type') !== HolidayTemplate::TYPE_FIXED) {
                return;
            }

            $month = (int) ($get('month') ?? 0);
            $day = (int) ($get('day') ?? 0);
            $year = (int) ($get('year') ?: now()->year);
            $countDays = max(1, (int) ($get('count_days') ?: 1));

            if ($month < 1 || $month > 12 || $day < 1 || $day > 31) {
                return;
            }

            $maxDaysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;
            $validDay = min($day, $maxDaysInMonth);

            $fromDate = Carbon::createFromDate($year, $month, $validDay)->startOfDay();
            $toDate = $fromDate->copy()->addDays($countDays - 1);

            $set('from_date', $fromDate->toDateString());
            $set('to_date', $toDate->toDateString());
        };

        $syncToDateFromCountDays = static function (Get $get, Set $set): void {
            $fromDateValue = $get('from_date');
            $countDays = max(1, (int) ($get('count_days') ?: 1));

            if (! $fromDateValue) {
                $syncDatesFromFixedDayMonth($get, $set);

                return;
            }

            $fromDate = Carbon::parse($fromDateValue)->startOfDay();
            $toDate = $fromDate->copy()->addDays($countDays - 1);

            $set('to_date', $toDate->toDateString());
            $set('year', (int) $fromDate->year);

            if ($get('type') === HolidayTemplate::TYPE_FIXED) {
                $set('month', (int) $fromDate->month);
                $set('day', (int) $fromDate->day);
            }
        };

        $syncCountDaysFromDateRange = static function (Get $get, Set $set): void {
            $fromDateValue = $get('from_date');
            $toDateValue = $get('to_date');

            if (! $fromDateValue || ! $toDateValue) {
                return;
            }

            $fromDate = Carbon::parse($fromDateValue)->startOfDay();
            $toDate = Carbon::parse($toDateValue)->startOfDay();

            if ($toDate->lt($fromDate)) {
                $toDate = $fromDate->copy();
                $set('to_date', $toDate->toDateString());
            }

            $set('count_days', (int) $fromDate->diffInDays($toDate) + 1);
            $set('year', (int) $fromDate->year);

            if ($get('type') === HolidayTemplate::TYPE_FIXED) {
                $set('month', (int) $fromDate->month);
                $set('day', (int) $fromDate->day);
            }
        };

        return $schema->components([
            Fieldset::make('Holiday Template Information')
                ->columnSpanFull()
                ->columns(4)
                ->schema([
                    TextInput::make('name')
                        ->label('Occasion / Holiday Name')
                        ->placeholder('e.g. Eid Al-Fitr, Unity Day')
                        ->maxLength(150)
                        ->required(),

                    Select::make('type')
                        ->label('Holiday Type')
                        ->options(HolidayTemplate::typeOptions())
                        ->default(HolidayTemplate::TYPE_FIXED)
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (?string $state, Get $get, Set $set) use ($syncDatesFromFixedDayMonth): void {
                            if ($state === HolidayTemplate::TYPE_HIJRI) {
                                $set('status', HolidayTemplate::STATUS_TENTATIVE);
                            } else {
                                $set('status', HolidayTemplate::STATUS_CONFIRMED);
                                $syncDatesFromFixedDayMonth($get, $set);
                            }
                        }),

                    Select::make('status')
                        ->label('Status')
                        ->options(HolidayTemplate::statusOptions())
                        ->default(HolidayTemplate::STATUS_CONFIRMED)
                        ->required(),

                    TextInput::make('count_days')
                        ->label('Number of Days')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(60)
                        ->default(1)
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Get $get, Set $set) => $syncToDateFromCountDays($get, $set)),
                ]),

            Fieldset::make('Template & Calendar Dates')
                ->columnSpanFull()
                ->columns(5)
                ->schema([
                    Select::make('month')
                        ->label(fn (Get $get): string => $get('type') === HolidayTemplate::TYPE_HIJRI ? 'Hijri Month' : 'Month')
                        ->options(fn (Get $get): array => HolidayTemplate::monthOptions((string) $get('type')))
                        ->searchable()
                        ->required(fn (Get $get): bool => $get('type') === HolidayTemplate::TYPE_FIXED)
                        ->live()
                        ->afterStateUpdated(fn (Get $get, Set $set) => $syncDatesFromFixedDayMonth($get, $set)),

                    Select::make('day')
                        ->label(fn (Get $get): string => $get('type') === HolidayTemplate::TYPE_HIJRI ? 'Hijri Day' : 'Day')
                        ->options(fn (Get $get): array => HolidayTemplate::dayOptions((string) $get('type')))
                        ->searchable()
                        ->required(fn (Get $get): bool => $get('type') === HolidayTemplate::TYPE_FIXED)
                        ->live()
                        ->afterStateUpdated(fn (Get $get, Set $set) => $syncDatesFromFixedDayMonth($get, $set)),

                    Select::make('year')
                        ->label('Year')
                        ->options(HolidayTemplate::yearOptions())
                        ->default((int) now()->year)
                        ->searchable()
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn (Get $get, Set $set) => $syncDatesFromFixedDayMonth($get, $set)),

                    DatePicker::make('from_date')
                        ->label('From Date (Gregorian)')
                        ->default(now()->toDateString())
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn (Get $get, Set $set) => $syncToDateFromCountDays($get, $set)),

                    DatePicker::make('to_date')
                        ->label('To Date (Gregorian)')
                        ->default(now()->toDateString())
                        ->required()
                        ->afterOrEqual('from_date')
                        ->live()
                        ->afterStateUpdated(fn (Get $get, Set $set) => $syncCountDaysFromDateRange($get, $set)),
                ]),

            Fieldset::make('Settings & Branch Scope')
                ->columnSpanFull()
                ->columns(3)
                ->schema([
                    Toggle::make('is_recurring')
                        ->label('Recurring Yearly')
                        ->inline(false)
                        ->default(true),

                    Toggle::make('active')
                        ->label('Active')
                        ->inline(false)
                        ->default(true),

                    Toggle::make('all_branches')
                        ->label('All Branches')
                        ->inline(false)
                        ->default(true)
                        ->live(),

                    CheckboxList::make('branch_ids')
                        ->label('Included Branches')
                        ->options(fn (): array => Branch::query()
                            ->where('active', 1)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray())
                        ->searchable()
                        ->bulkToggleable()
                        ->columns(4)
                        ->columnSpanFull()
                        ->visible(fn (Get $get): bool => ! (bool) $get('all_branches'))
                        ->required(fn (Get $get): bool => ! (bool) $get('all_branches')),
                ]),

            Fieldset::make('Notes')
                ->columnSpanFull()
                ->columns(1)
                ->schema([
                    Textarea::make('notes')
                        ->label('Notes')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
