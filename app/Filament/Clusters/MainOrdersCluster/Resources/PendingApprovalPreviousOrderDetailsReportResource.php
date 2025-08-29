<?php

namespace App\Filament\Clusters\MainOrdersCluster\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use App\Filament\Clusters\MainOrdersCluster\Resources\PendingApprovalPreviousOrderDetailsReportResource\Pages\ListPendingApprovalPreviousOrderDetailsReports;
use App\Filament\Clusters\MainOrdersCluster;
use App\Filament\Clusters\MainOrdersCluster\Resources\PendingApprovalPreviousOrderDetailsReportResource\Pages;
use App\Filament\Clusters\MainOrdersCluster\Resources\PendingApprovalPreviousOrderDetailsReportResource\RelationManagers;
use App\Filament\Clusters\OrderReportsCluster;
use App\Models\FakeModelReports\PendingApprovalPreviousOrderDetailsReport;
use Filament\Forms;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PendingApprovalPreviousOrderDetailsReportResource extends Resource
{
    protected static ?string $model = PendingApprovalPreviousOrderDetailsReport::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::ArrowsRightLeft;

    protected static ?string $cluster = OrderReportsCluster::class;
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 3;

    public static function getLabel(): ?string
    {
        return 'Reorders from Stock Out';
    }

    public static function getModelLabel(): string
    {
        return 'Reorders from Stock Out';
    }
    public static function getPluralLabel(): ?string
    {
        return 'Reorders from Stock Out';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Reorders from Stock Out';
    }
    public static function table(Table $table): Table
    {
        return $table->deferFilters(false)
            ->columns([
                //
            ])
            ->filters([
                Filter::make('show_extra_fields')
                    ->label('')
                    ->schema([
                        Toggle::make('group_by_order')
                            ->inline(false)
                            ->label('Group by Order')
                    ], FiltersLayout::AboveContent),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPendingApprovalPreviousOrderDetailsReports::route('/'),
        ];
    }
}
