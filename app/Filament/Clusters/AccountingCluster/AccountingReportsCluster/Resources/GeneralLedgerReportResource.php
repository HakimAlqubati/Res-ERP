<?php

namespace App\Filament\Clusters\AccountingCluster\AccountingReportsCluster\Resources;

use App\Filament\Clusters\AccountingReportCluster;
use App\Filament\Clusters\AccountingCluster\AccountingReportsCluster\Resources\GeneralLedgerReportResource\Pages;
use App\Models\JournalEntryLine;
use Filament\Resources\Resource;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Tables\Table;
use App\Models\Account;
use Filament\Pages\SubNavigationPosition;

class GeneralLedgerReportResource extends Resource
{
    protected static ?string $model = JournalEntryLine::class;

    protected static ?string $slug = 'general-ledger-report';

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $label = 'General Ledger - دفتر الأستاذ';

    protected static ?string $cluster = AccountingReportCluster::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 5;
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
            Filter::make('filters')
                ->form([
                    Select::make('account_id')
                        ->label('Account')
                        ->options(Account::pluck('name', 'id'))
                        ->searchable(),
                    DatePicker::make('start_date')->label('From'),
                    DatePicker::make('end_date')->label('To'),
                ])
        ], layout: FiltersLayout::AboveContent);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGeneralLedgerReport::route('/'),
        ];
    }
}
