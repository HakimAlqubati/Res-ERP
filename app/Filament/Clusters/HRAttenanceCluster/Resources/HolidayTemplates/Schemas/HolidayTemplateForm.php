<?php

declare(strict_types=1);

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\HolidayTemplates\Schemas;

use App\Models\Branch;
use App\Models\HolidayTemplate;
use App\Modules\HR\Holidays\Rules\ValidHolidayTemplateDayRule;
use Filament\Forms\Components\CheckboxList;
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
        return $schema->components([
            Fieldset::make('Holiday Template Definition')
                ->columnSpanFull()
                ->columns(3)
                ->schema([
                    TextInput::make('name')
                        ->label('Occasion / Holiday Name')
                        ->placeholder('e.g. Eid Al-Fitr, Unity Day')
                        ->maxLength(150)
                        ->unique(HolidayTemplate::class, 'name', ignoreRecord: true)
                        ->required(),

                    Select::make('type')
                        ->label('Calendar Type')
                        ->options(HolidayTemplate::typeOptions())
                        ->default(HolidayTemplate::TYPE_FIXED)
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (Set $set): void {
                            $set('month', null);
                            $set('day', null);
                        }),

                    TextInput::make('count_days')
                        ->label('Default Number of Days')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(60)
                        ->default(1)
                        ->required(),
                ]),

            Fieldset::make('Recurring Day & Month (Without Year)')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    Select::make('month')
                        ->key(fn (Get $get): string => 'holiday_month_' . ((string) ($get('type') ?: HolidayTemplate::TYPE_FIXED)))
                        ->label(fn (Get $get): string => $get('type') === HolidayTemplate::TYPE_HIJRI ? 'Hijri Month' : 'Gregorian Month')
                        ->options(fn (Get $get): array => HolidayTemplate::monthOptions((string) $get('type')))
                        ->searchable()
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (mixed $state, Get $get, Set $set): void {
                            $day = $get('day');

                            if (blank($day)) {
                                return;
                            }

                            $type = (string) ($get('type') ?: HolidayTemplate::TYPE_FIXED);
                            $month = filled($state) ? (int) $state : null;

                            if (! HolidayTemplate::isValidDayForMonth($type, $month, (int) $day)) {
                                $set('day', null);
                            }
                        }),

                    Select::make('day')
                        ->label(function (Get $get): string {
                            $type = (string) ($get('type') ?: HolidayTemplate::TYPE_FIXED);
                            $month = filled($get('month')) ? (int) $get('month') : null;
                            $maxDay = HolidayTemplate::maxDayForMonth($type, $month);
                            $prefix = $type === HolidayTemplate::TYPE_HIJRI ? 'Hijri Day' : 'Gregorian Day';

                            return "{$prefix} (1 - {$maxDay})";
                        })
                        ->options(function (Get $get): array {
                            $type = (string) ($get('type') ?: HolidayTemplate::TYPE_FIXED);
                            $month = filled($get('month')) ? (int) $get('month') : null;

                            return HolidayTemplate::dayOptions($type, $month);
                        })
                        ->rule(fn (Get $get): ValidHolidayTemplateDayRule => new ValidHolidayTemplateDayRule(
                            type: (string) ($get('type') ?: HolidayTemplate::TYPE_FIXED),
                            month: filled($get('month')) ? (int) $get('month') : null,
                        ))
                        ->searchable()
                        ->required(),
                ]),

            Fieldset::make('Status & Branch Scope')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
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
