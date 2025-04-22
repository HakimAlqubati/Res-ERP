<?php

namespace App\Filament\Clusters\AccountingCluster\AccountingReportsCluster\Resources;

use App\Filament\Clusters\AccountingReportCluster;
use App\Filament\Clusters\AccountingReportsCluster;
use App\Filament\Clusters\AccountingCluster\AccountingReportsCluster\Resources\TrialBalanceReportResource\Pages;
use App\Models\Account;
use App\Models\JournalEntryLine;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TrialBalanceReportResource extends Resource
{
    protected static ?string $model = JournalEntryLine::class;

    protected static ?string $slug = 'trial-balance-report';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $cluster = AccountingReportCluster::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 1;
    
    protected static ?string $label = 'Trial Balance- ميزان المراجعة';

    protected static ?string $modelLabel = 'Trial Balance- ميزان المراجعة';
 
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
            'index' => Pages\ListTrialBalanceReport::route('/'),
        ];
    }
}
