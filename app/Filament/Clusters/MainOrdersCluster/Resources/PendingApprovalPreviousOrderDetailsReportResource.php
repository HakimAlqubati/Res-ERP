<?php

namespace App\Filament\Clusters\MainOrdersCluster\Resources;

use App\Filament\Clusters\MainOrdersCluster;
use App\Filament\Clusters\MainOrdersCluster\Resources\PendingApprovalPreviousOrderDetailsReportResource\Pages;
use App\Filament\Clusters\MainOrdersCluster\Resources\PendingApprovalPreviousOrderDetailsReportResource\RelationManagers;
use App\Models\FakeModelReports\PendingApprovalPreviousOrderDetailsReport;
use Filament\Forms;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PendingApprovalPreviousOrderDetailsReportResource extends Resource
{
    protected static ?string $model = PendingApprovalPreviousOrderDetailsReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = MainOrdersCluster::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 5;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                Filter::make('show_extra_fields')
                    ->label('')
                    ->form([
                        Toggle::make('only_available')
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
            'index' => Pages\ListPendingApprovalPreviousOrderDetailsReports::route('/'),
        ];
    }
}
