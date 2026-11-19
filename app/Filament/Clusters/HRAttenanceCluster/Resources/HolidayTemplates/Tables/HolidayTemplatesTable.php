<?php

declare(strict_types=1);

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\HolidayTemplates\Tables;

use App\Filament\Tables\Columns\SoftDeleteColumn;
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
                    ->label('Calendar Type')
                    ->badge()
                    ->icon(fn (?string $state): string => HolidayTemplate::getTypeIcon($state))
                    ->formatStateUsing(fn (?string $state): string => HolidayTemplate::getTypeLabel($state))
                    ->color(fn (?string $state): string => HolidayTemplate::getTypeColor($state))
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('day_month')
                    ->label('Day / Month')
                    ->state(fn (HolidayTemplate $record): string => $record->formattedDayMonth())
                    ->alignCenter(),

                TextColumn::make('count_days')
                    ->label('Default Days')
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

                TextColumn::make('occurrences_count')
                    ->label('Occurrences')
                    ->badge()
                    ->color('primary')
                    ->alignCenter()
                    ->toggleable(),

                IconColumn::make('active')
                    ->label('Active')
                    ->boolean()
                    ->sortable()
                    ->alignCenter(),

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
                    ->label('Calendar Type')
                    ->options(HolidayTemplate::typeOptions()),

                TernaryFilter::make('active')
                    ->label('Active'),
            ], FiltersLayout::Modal)
            ->filtersFormColumns(3)
            ->recordActions([
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
