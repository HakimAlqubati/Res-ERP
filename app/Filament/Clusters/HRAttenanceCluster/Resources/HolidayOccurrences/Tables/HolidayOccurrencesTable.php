<?php

declare(strict_types=1);

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\HolidayOccurrences\Tables;

use App\Filament\Clusters\HRAttenanceCluster\Resources\HolidayOccurrences\Actions\HolidayOccurrenceTableActions;
use App\Filament\Tables\Columns\SoftDeleteColumn;
use App\Models\HolidayOccurrence;
use App\Models\HolidayTemplate;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class HolidayOccurrencesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->striped()
            ->defaultSort('from_date', 'desc')
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
                    ->label('Calendar Type')
                    ->badge()
                    ->icon(fn (?string $state): string => HolidayTemplate::getTypeIcon($state))
                    ->formatStateUsing(fn (?string $state): string => HolidayTemplate::getTypeLabel($state))
                    ->color(fn (?string $state): string => HolidayTemplate::getTypeColor($state))
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('year')
                    ->label('Year')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('hijri_year')
                    ->label('Hijri Year')
                    ->placeholder('-')
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(),

                TextColumn::make('expected_from_date')
                    ->label('Expected Start')
                    ->date('Y-m-d')
                    ->placeholder('-')
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('from_date')
                    ->label('Actual From Date')
                    ->date('Y-m-d')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('to_date')
                    ->label('Actual To Date')
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
                    ->state(fn (HolidayOccurrence $record): string => $record->branchesSummary())
                    ->badge()
                    ->color(fn (HolidayOccurrence $record): string => $record->all_branches ? 'success' : 'info')
                    ->alignCenter()
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->icon(fn (?string $state): string => HolidayOccurrence::getStatusIcon($state))
                    ->formatStateUsing(fn (?string $state): string => HolidayOccurrence::getStatusLabel($state))
                    ->color(fn (?string $state): string => HolidayOccurrence::getStatusColor($state))
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
            ])
            ->filters([
                TrashedFilter::make(),

                SelectFilter::make('year')
                    ->label('Year')
                    ->options(HolidayOccurrence::yearOptions()),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options(HolidayOccurrence::statusOptions()),

                SelectFilter::make('type')
                    ->label('Calendar Type')
                    ->options(HolidayTemplate::typeOptions()),

                TernaryFilter::make('active')
                    ->label('Active'),
            ], FiltersLayout::Modal)
            ->filtersFormColumns(4)
            ->recordActions([
                HolidayOccurrenceTableActions::confirmDatesAction(),
                HolidayOccurrenceTableActions::shiftDatesAction(),
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
