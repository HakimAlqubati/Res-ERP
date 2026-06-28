<?php

namespace App\Filament\Resources\AdvanceWages;

use App\Filament\Clusters\HRSalaryCluster;
use App\Filament\Resources\AdvanceWages\Pages\CreateAdvanceWage;
use App\Filament\Resources\AdvanceWages\Pages\EditAdvanceWage;
use App\Filament\Resources\AdvanceWages\Pages\ListAdvanceWages;
use App\Filament\Resources\AdvanceWages\Schemas\AdvanceWageForm;
use App\Filament\Resources\AdvanceWages\Tables\AdvanceWagesTable;
use App\Models\AdvanceWage;
use BackedEnum;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AdvanceWageResource extends Resource
{
    protected static ?string $model = AdvanceWage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'employee.name';

    protected static ?string $cluster = HRSalaryCluster::class;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return AdvanceWageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AdvanceWagesTable::configure($table);
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
            'index' => ListAdvanceWages::route('/'),
            'create' => CreateAdvanceWage::route('/create'),
            'edit' => EditAdvanceWage::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function canViewAny(): bool
    {
        if (isSuperAdmin() || isSystemManager() || isBranchManager() || isFinanceManager()) {
            return true;
        }

        return false;
    }
    
    public static function shouldRegisterNavigation(): bool
    {
           if (isSuperAdmin() || isSystemManager() || isBranchManager() || isFinanceManager()) {
            return true;
        }

        return false;
    }
}
