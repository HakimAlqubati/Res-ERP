<?php

declare(strict_types=1);

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\HolidayTemplates\Tables;

use App\Filament\Tables\Columns\SoftDeleteColumn;
use App\Models\HolidayTemplate;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class HolidayTemplatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->striped()
            ->defaultSort('id', 'desc')
            ->columns([
                SoftDeleteColumn::make(),

                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('name')
                    ->label('Holiday Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->icon(fn (?string $state): string => HolidayTemplate::getTypeIcon($state))
                    ->formatStateUsing(fn (?string $state): string => HolidayTemplate::getTypeLabel($state))
                    ->color(fn (?string $state): string => HolidayTemplate::getTypeColor($state))
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('day_month')
                    ->label('Day / Month')
                    ->state(fn (HolidayTemplate $record): string => $record->formattedDayMonth())
                    ->alignCenter()
                    ->toggleable(),

                TextColumn::make('year')
                    ->label('Year')
                    ->placeholder('Yearly')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('from_date')
                    ->label('From Date')
                    ->date('Y-m-d')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('to_date')
                    ->label('To Date')
                    ->date('Y-m-d')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('count_days')
                    ->label('Days')
                    ->badge()
                    ->color('gray')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('branches_summary')
                    ->label('Branches')
                    ->state(fn (HolidayTemplate $record): string => $record->branchesSummary())
                    ->badge()
                    ->color(fn (HolidayTemplate $record): string => $record->all_branches ? 'success' : 'info')
                    ->alignCenter()
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->icon(fn (?string $state): string => HolidayTemplate::getStatusIcon($state))
                    ->formatStateUsing(fn (?string $state): string => HolidayTemplate::getStatusLabel($state))
                    ->color(fn (?string $state): string => HolidayTemplate::getStatusColor($state))
                    ->sortable()
                    ->alignCenter(),

                IconColumn::make('active')
                    ->label('Active')
                    ->boolean()
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('confirmedBy.name')
                    ->label('Confirmed By')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('createdBy.name')
                    ->label('Created By')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),

                SelectFilter::make('type')
                    ->label('Type')
                    ->options(HolidayTemplate::typeOptions()),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options(HolidayTemplate::statusOptions()),

                SelectFilter::make('year')
                    ->label('Year')
                    ->options(HolidayTemplate::yearOptions()),

                TernaryFilter::make('active')
                    ->label('Active'),
            ], FiltersLayout::Modal)
            ->filtersFormColumns(4)
            ->recordActions([
                Action::make('confirm')
                    ->label('Confirm Dates')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (HolidayTemplate $record): bool => $record->isTentative())
                    ->fillForm(fn (HolidayTemplate $record): array => [
                        'from_date'  => $record->from_date?->toDateString() ?? now()->toDateString(),
                        'to_date'    => $record->to_date?->toDateString() ?? now()->toDateString(),
                        'count_days' => $record->count_days ?: 1,
                    ])
                    ->schema([
                        Fieldset::make('Confirm Official Holiday Dates')
                            ->columns(3)
                            ->schema([
                                DatePicker::make('from_date')
                                    ->label('From Date')
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (?string $state, Get $get, Set $set): void {
                                        if (! $state) {
                                            return;
                                        }
                                        $countDays = max(1, (int) ($get('count_days') ?: 1));
                                        $toDate = Carbon::parse($state)->startOfDay()->addDays($countDays - 1);
                                        $set('to_date', $toDate->toDateString());
                                    }),

                                TextInput::make('count_days')
                                    ->label('Number of Days')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(60)
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Get $get, Set $set): void {
                                        $fromDate = $get('from_date');
                                        if (! $fromDate) {
                                            return;
                                        }
                                        $countDays = max(1, (int) ($state ?: 1));
                                        $toDate = Carbon::parse($fromDate)->startOfDay()->addDays($countDays - 1);
                                        $set('to_date', $toDate->toDateString());
                                    }),

                                DatePicker::make('to_date')
                                    ->label('To Date')
                                    ->required()
                                    ->afterOrEqual('from_date')
                                    ->live()
                                    ->afterStateUpdated(function (?string $state, Get $get, Set $set): void {
                                        $fromDate = $get('from_date');
                                        if (! $fromDate || ! $state) {
                                            return;
                                        }
                                        $from = Carbon::parse($fromDate)->startOfDay();
                                        $to = Carbon::parse($state)->startOfDay();
                                        if ($to->gte($from)) {
                                            $set('count_days', (int) $from->diffInDays($to) + 1);
                                        }
                                    }),
                            ]),
                    ])
                    ->action(function (HolidayTemplate $record, array $data): void {
                        $fromDate = Carbon::parse($data['from_date'])->startOfDay();
                        $toDate = Carbon::parse($data['to_date'])->startOfDay();

                        $record->update([
                            'from_date'    => $fromDate->toDateString(),
                            'to_date'      => $toDate->toDateString(),
                            'count_days'   => (int) $fromDate->diffInDays($toDate) + 1,
                            'year'         => (int) $fromDate->year,
                            'status'       => HolidayTemplate::STATUS_CONFIRMED,
                            'confirmed_by' => auth()->id(),
                            'confirmed_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Holiday template confirmed successfully.')
                            ->success()
                            ->send();
                    }),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
