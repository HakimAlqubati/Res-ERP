<?php

namespace App\Filament\Clusters\HRCluster\Resources\EmployeeServiceTerminations;

use App\Filament\Clusters\HRCluster;
use App\Filament\Clusters\HRCluster\Resources\EmployeeServiceTerminations\Pages\CreateEmployeeServiceTermination;
use App\Filament\Clusters\HRCluster\Resources\EmployeeServiceTerminations\Pages\EditEmployeeServiceTermination;
use App\Filament\Clusters\HRCluster\Resources\EmployeeServiceTerminations\Pages\ListEmployeeServiceTerminations;
use App\Filament\Clusters\HRCluster\Resources\EmployeeServiceTerminations\Schemas\EmployeeServiceTerminationForm;
use App\Filament\Clusters\HRCluster\Resources\EmployeeServiceTerminations\Tables\EmployeeServiceTerminationsTable;
use App\Models\EmployeeServiceTermination;
use BackedEnum;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EmployeeServiceTerminationResource extends Resource
{
    protected static ?string $model = EmployeeServiceTermination::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserMinus;

    protected static ?string $slug = 'staff-service-terminations';

    protected static ?string $cluster = HRCluster::class;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 2;
    protected static ?string $recordTitleAttribute = 'employee.name';

        public static function getNavigationLabel(): string
    {
        return __('lang.terminate_staff');
    }

    public static function getPluralLabel(): ?string
    {
        return __('lang.terminate_staff');
    }

    public static function getModelLabel(): string
    {
        return __('lang.terminate_staff');
    }

    public static function getLabel(): ?string
    {
        return __('lang.terminate_staff');
    }


    public static function form(Schema $schema): Schema
    {
        return EmployeeServiceTerminationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmployeeServiceTerminationsTable::configure($table);
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
            'index' => ListEmployeeServiceTerminations::route('/'),
            'create' => CreateEmployeeServiceTermination::route('/create'),
            'edit' => EditEmployeeServiceTermination::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $pendingCount = static::getEloquentQuery() 
        ->pending()
        ->count();
        return 'Pending (' . $pendingCount . ')';
    }

    
    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }
    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
          ->forBranchManager()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function canCreate(): bool
    {

        if (isSystemManager() || isSuperAdmin() || isHR()) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (isSystemManager() || isBranchManager() || isSuperAdmin() || isHR()) {
            return true;
        }

        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
        if (isSystemManager() || isBranchManager() || isSuperAdmin() || isHR()) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (isSuperAdmin() || isBranchManager() || isSystemManager()  || isFinanceManager() || isHR()) {
            return true;
        }

        return false;
    }

    public static function canViewAny(): bool
    {
        if (isSuperAdmin() || isSystemManager() || isBranchManager() || isFinanceManager() || isHR()) {
            return true;
        }

        return false;
    }
}
