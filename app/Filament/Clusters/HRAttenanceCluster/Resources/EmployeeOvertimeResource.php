<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Schemas\Schema;


use App\Filament\Clusters\HRAttenanceCluster\Resources\EmployeeOvertimeResource\Pages;
use App\Filament\Clusters\HRAttenanceCluster;
use App\Filament\Clusters\HRAttenanceCluster\Resources\EmployeeOvertimeResource\Schema\EmployeeOvertimeForm;
use App\Filament\Clusters\HRAttenanceCluster\Resources\EmployeeOvertimeResource\Tables\EmployeeOvertimeTable;

use App\Models\EmployeeOvertime;

use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;

use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EmployeeOvertimeResource extends Resource
{
    protected static ?string $model = EmployeeOvertime::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::Briefcase;

    protected static ?string $cluster                             = HRAttenanceCluster::class;
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function getModelLabel(): string
    {
        return isStuff() ? __('lang.my_overtime') : __('lang.staff_overtime');
    }
    public static function getPluralLabel(): ?string
    {
        return isStuff() ? __('lang.my_overtime') : __('lang.staff_overtime');
    }
    public static function getNavigationLabel(): string
    {
        return isStuff() ? __('lang.my_overtime') : __('lang.staff_overtime');
    }
    protected static ?int $navigationSort = 5;
    public static function form(Schema $schema): Schema
    {
        return EmployeeOvertimeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmployeeOvertimeTable::configure($table);
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
            'index'  => Pages\ListEmployeeOvertimes::route('/'),
            'create' => Pages\CreateEmployeeOvertime::route('/create'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::forBranchManager()
            ->when(isBranchUser(), function (Builder $query) {
                $query->whereHas('employee', function (Builder $query) {
                    $query->where('branch_id', auth()->user()->branch_id);
                });
            })
            ->count();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->when(isBranchUser(), function (Builder $query) {
                $query->whereHas('employee', function (Builder $query) {
                    $query->where('branch_id', auth()->user()->branch_id);
                });
            })
            ->forBranchManager()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function canCreate(): bool
    {
        if (isSystemManager() || isSuperAdmin() || isBranchManager()) {
            return true;
        }
        return false;
    }

    public static function canForceDelete(Model $record): bool
    {
        if (isSuperAdmin()) {
            return true;
        }
        return false;
    }

    public static function canForceDeleteAny(): bool
    {
        if (isSuperAdmin()) {
            return true;
        }
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (isSuperAdmin()) {
            return true;
        }
        return false;
    }
    public static function canDeleteAny(): bool
    {
        if (isSuperAdmin()) {
            return true;
        }
        return false;
    }
}
