<?php

namespace App\Filament\Clusters\AccountingCluster\AccountingReportsCluster\Resources;

use App\Filament\Clusters\AccountingCluster\AccountingReportsCluster\Resources\BalanceSheetReportResource\Pages;
use App\Filament\Clusters\AccountingReportCluster;
use App\Models\JournalEntryLine;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;

class BalanceSheetReportResource extends Resource
{
    protected static ?string $model = JournalEntryLine::class;

    protected static ?string $slug = 'balance-sheet-report';

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $label = 'Balance Sheet - الميزانية العمومية';

    protected static ?string $modelLabel = 'Balance Sheet - الميزانية العمومية';
    protected static ?string $cluster = AccountingReportCluster::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 3;
    public static function getLabel(): ?string
    {
        return self::$label;
    }

    public static function getPluralLabel(): ?string
    {
        return self::$label;
    }

    public static function table(Table $table): Table
    {
        return $table->filters([
            Filter::make('date_range')
                ->form([
                    \Filament\Forms\Components\DatePicker::make('start_date')->label('From'),
                    \Filament\Forms\Components\DatePicker::make('end_date')->label('To'),
                ])
        ], layout: FiltersLayout::AboveContent);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBalanceSheetReport::route('/'),
        ];
    }
}
