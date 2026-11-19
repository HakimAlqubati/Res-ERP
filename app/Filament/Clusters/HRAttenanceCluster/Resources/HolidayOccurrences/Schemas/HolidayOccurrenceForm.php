<?php

declare(strict_types=1);

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\HolidayOccurrences\Schemas;

use App\Models\Branch;
use App\Models\HolidayOccurrence;
use App\Models\HolidayTemplate;
use App\Modules\HR\Holidays\Builders\HolidayOccurrencePayloadBuilder;
use App\Modules\HR\Holidays\Contracts\HolidayOccurrenceServiceInterface;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class HolidayOccurrenceForm
{
    public static function configure(Schema $schema): Schema
    {
        $applyTemplatePreview = static function (Get $get, Set $set): void {
            $templateId = (int) ($get('holiday_template_id') ?? 0);
            $year = (int) ($get('year') ?: now()->year);

            if ($templateId <= 0) {
                return;
            }

            $template = HolidayTemplate::query()->find($templateId);
            if (! $template) {
                return;
            }

            $preview = app(HolidayOccurrenceServiceInterface::class)
                ->previewForTemplateAndYear($template, $year);

            $set('name', $template->name);
            $set('type', $template->type);
            $set('hijri_year', $preview->hijriYear);
            $set('expected_from_date', $preview->fromDate);
            $set('expected_to_date', $preview->toDate);
            $set('from_date', $preview->fromDate);
            $set('to_date', $preview->toDate);
            $set('count_days', $preview->countDays);
            $set('status', $template->isHijri()
                ? HolidayOccurrence::STATUS_TENTATIVE
                : HolidayOccurrence::STATUS_CONFIRMED);
            $set('all_branches', $template->all_branches);
            $set('branch_ids', $template->branch_ids ?? []);
        };

        $syncFromStartAndDays = static function (Get $get, Set $set): void {
            $fromDate = (string) ($get('from_date') ?? '');
            $countDays = max(1, (int) ($get('count_days') ?: 1));

            if ($fromDate === '') {
                return;
            }

            $calculated = HolidayOccurrencePayloadBuilder::calculateFromStartAndDays($fromDate, $countDays);

            $set('to_date', $calculated['to_date']);
            $set('year', $calculated['year']);
        };

        $syncFromDateRange = static function (Get $get, Set $set): void {
            $fromDate = (string) ($get('from_date') ?? '');
            $toDate = (string) ($get('to_date') ?? '');

            if ($fromDate === '' || $toDate === '') {
                return;
            }

            $calculated = HolidayOccurrencePayloadBuilder::calculateFromDateRange($fromDate, $toDate);

            $set('to_date', $calculated['to_date']);
            $set('count_days', $calculated['count_days']);
            $set('year', $calculated['year']);
        };

        return $schema->components([
            Fieldset::make('Template & Occasion')
                ->columnSpanFull()
                ->columns(4)
                ->schema([
                    Select::make('holiday_template_id')
                        ->label('Holiday Template (Optional)')
                        ->options(fn (): array => HolidayTemplate::query()
                            ->active()
                            ->orderBy('name')
                            ->get()
                            ->mapWithKeys(fn (HolidayTemplate $tpl): array => [
                                $tpl->id => "{$tpl->name} ({$tpl->formattedDayMonth()})",
                            ])
                            ->toArray())
                        ->searchable()
                        ->preload()
                        ->placeholder('Select template to auto-fill expected dates')
                        ->live()
                        ->afterStateUpdated(fn (Get $get, Set $set) => $applyTemplatePreview($get, $set)),

                    Select::make('year')
                        ->label('Gregorian Year')
                        ->options(HolidayOccurrence::yearOptions())
                        ->default((int) now()->year)
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn (Get $get, Set $set) => $applyTemplatePreview($get, $set)),

                    TextInput::make('name')
                        ->label('Holiday Name')
                        ->maxLength(150)
                        ->required(),

                    Select::make('type')
                        ->label('Calendar Type')
                        ->options(HolidayTemplate::typeOptions())
                        ->default(HolidayTemplate::TYPE_FIXED)
                        ->required(),
                ]),

            Fieldset::make('Actual Effective Dates (Editable by Admin)')
                ->columnSpanFull()
                ->columns(4)
                ->schema([
                    DatePicker::make('from_date')
                        ->label('Actual From Date')
                        ->default(now()->toDateString())
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn (Get $get, Set $set) => $syncFromStartAndDays($get, $set)),

                    TextInput::make('count_days')
                        ->label('Number of Days')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(60)
                        ->default(1)
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Get $get, Set $set) => $syncFromStartAndDays($get, $set)),

                    DatePicker::make('to_date')
                        ->label('Actual To Date')
                        ->default(now()->toDateString())
                        ->required()
                        ->afterOrEqual('from_date')
                        ->live()
                        ->afterStateUpdated(fn (Get $get, Set $set) => $syncFromDateRange($get, $set)),

                    Select::make('status')
                        ->label('Approval Status')
                        ->options(HolidayOccurrence::statusOptions())
                        ->default(HolidayOccurrence::STATUS_TENTATIVE)
                        ->required(),

                    Hidden::make('hijri_year'),
                    Hidden::make('expected_from_date'),
                    Hidden::make('expected_to_date'),
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
