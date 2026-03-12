<?php

namespace App\Filament\Resources\BranchSalesReports;

use App\Filament\Resources\BranchSalesReports\Pages\CreateBranchSalesReport;
use App\Filament\Resources\BranchSalesReports\Pages\EditBranchSalesReport;
use App\Filament\Resources\BranchSalesReports\Pages\ListBranchSalesReports;
use App\Filament\Resources\BranchSalesReports\Schemas\BranchSalesReportForm;
use App\Filament\Resources\BranchSalesReports\Tables\BranchSalesReportsTable;
use App\Models\BranchSalesReport;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BranchSalesReportResource extends Resource
{
    protected static ?string $model = BranchSalesReport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'branch';

    public static function form(Schema $schema): Schema
    {
        return BranchSalesReportForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BranchSalesReportsTable::configure($table);
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
            'index' => ListBranchSalesReports::route('/'),
            'create' => CreateBranchSalesReport::route('/create'),
            'edit' => EditBranchSalesReport::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function canCreate(): bool
    {

        if (isFinanceManager() || isSuperAdmin()) {
            return true;
        }
        return false;
    }

    public static function canViewAny(): bool
    {
        if (isSuperAdmin() ||  isFinanceManager()) {
            return true;
        }
        return false;
    }
}
