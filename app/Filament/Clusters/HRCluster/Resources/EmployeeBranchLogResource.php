<?php

namespace App\Filament\Clusters\HRCluster\Resources;

use App\Filament\Clusters\HRCluster;
use App\Filament\Clusters\HRCluster\Resources\EmployeeBranchLogResource\Pages\ListEmployeeBranchLogs;
use App\Filament\Clusters\HRCluster\Resources\EmployeeBranchLogResource\Tables\EmployeeBranchLogTable;
use App\Models\EmployeeBranchLog;
use BackedEnum;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EmployeeBranchLogResource extends Resource
{
    protected static ?string $model = EmployeeBranchLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ArrowsRightLeft;

    protected static ?string $slug = 'employee-branch-transfers';

    protected static ?string $cluster = HRCluster::class;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return __('lang.employee_branch_transfer');
    }

    public static function getPluralLabel(): string
    {
        return __('lang.employee_branch_transfers');
    }

    public static function getNavigationLabel(): string
    {
        return __('lang.employee_branch_transfers');
    }

    public static function table(Table $table): Table
    {
        return EmployeeBranchLogTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmployeeBranchLogs::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = EmployeeBranchLog::query()
            ->forBranchManager()
            ->transferredEmployees()
            ->count();

        return (string) $count;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'primary';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->forBranchManager()
            ->transferredEmployees()
            ->with([
                'employee:id,name',
                'branch:id,name',
                'createdBy:id,name',
            ]);
    }

    public static function canViewAny(): bool
    {
        if (isSuperAdmin() || isSystemManager() || isBranchManager() || isHR()) {
            return true;
        }

        return false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
