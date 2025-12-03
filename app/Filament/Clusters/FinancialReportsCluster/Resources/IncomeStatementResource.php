<?php

namespace App\Filament\Clusters\FinancialReportsCluster\Resources;

use App\Filament\Clusters\FinancialReportsCluster;
use App\Filament\Clusters\FinancialReportsCluster\Resources\IncomeStatementResource\Pages;
use App\Models\FinancialTransaction;
use App\Models\Branch;
use App\Models\Store;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;

class IncomeStatementResource extends Resource
{
    protected static ?string $model = FinancialTransaction::class;

    protected static ?string $slug = 'income-statement';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $cluster = FinancialReportsCluster::class;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 0;

    public static function getLabel(): ?string
    {
        return __('Income Statement');
    }

    public static function getNavigationLabel(): string
    {
        return __('Income Statement');
    }

    public static function getPluralLabel(): ?string
    {
        return __('Income Statements');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Custom view used
            ])
            ->deferFilters(false)
            ->filtersFormColumns(3)
            ->filters([
                Tables\Filters\Filter::make('date_range')
                    ->label(__('Date Range'))
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('start_date')
                            ->label(__('Start Date'))
                            ->default(now()->startOfYear()),
                        \Filament\Forms\Components\DatePicker::make('end_date')
                            ->label(__('End Date'))
                            ->default(now()->endOfYear()),
                    ]),
                Tables\Filters\SelectFilter::make('branch_id')
                    ->label(__('Branch'))
                    ->searchable()
                    ->preload()->placeholder(__('Choose Branch'))
                    ->options(function () {
                        return Branch::query()
                            ->limit(2) // العدد الذي تريده
                            ->pluck('name', 'id');
                    })

                    // 2. عند الكتابة في البحث: ابحث في جدول الفروع بالكامل
                    ->getSearchResultsUsing(function (string $search) {
                        return Branch::query()
                            ->where('name', 'like', "%{$search}%")
                            ->limit(50) // حدد عدد نتائج البحث
                            ->pluck('name', 'id');
                    })

                    // 3. عند اختيار قيمة (للحفاظ على الاسم ظاهراً بعد الاختيار)
                    ->getOptionLabelUsing(fn($value) => Branch::find($value)?->name),
            ])
            ->actions([
                // No actions
            ])
            ->bulkActions([
                // No bulk actions
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIncomeStatement::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
