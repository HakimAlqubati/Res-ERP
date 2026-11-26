<?php

declare(strict_types=1);

namespace App\Filament\Clusters\HRSalaryCluster\Resources\SalaryIncreaseRequests;

use App\Filament\Clusters\HRSalaryCluster;
use App\Filament\Clusters\HRSalaryCluster\Resources\SalaryIncreaseRequests\Pages\CreateSalaryIncreaseRequest;
use App\Filament\Clusters\HRSalaryCluster\Resources\SalaryIncreaseRequests\Pages\EditSalaryIncreaseRequest;
use App\Filament\Clusters\HRSalaryCluster\Resources\SalaryIncreaseRequests\Pages\ListSalaryIncreaseRequests;
use App\Filament\Clusters\HRSalaryCluster\Resources\SalaryIncreaseRequests\Schemas\SalaryIncreaseRequestForm;
use App\Filament\Clusters\HRSalaryCluster\Resources\SalaryIncreaseRequests\Tables\SalaryIncreaseRequestsTable;
use App\Models\SalaryIncreaseRequest;
use App\Modules\HR\SalaryIncrease\Builders\SalaryIncreaseQueryBuilder;
use BackedEnum;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SalaryIncreaseRequestResource extends Resource
{
    protected static ?string $model = SalaryIncreaseRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowTrendingUp;

    protected static ?string $cluster = HRSalaryCluster::class;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'id';

    public static function getNavigationLabel(): string
    {
        return __('Salary Increase Requests');
    }

    public static function getPluralLabel(): ?string
    {
        return __('Salary Increase Requests');
    }

    public static function getModelLabel(): string
    {
        return __('Salary Increase Request');
    }

    public static function getNavigationBadge(): ?string
    {
        $pendingCount = static::getEloquentQuery()->pending()->count();

        return $pendingCount > 0 ? (string) $pendingCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return SalaryIncreaseRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SalaryIncreaseRequestsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListSalaryIncreaseRequests::route('/'),
            'create' => CreateSalaryIncreaseRequest::route('/create'),
            'edit'   => EditSalaryIncreaseRequest::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return SalaryIncreaseQueryBuilder::make(parent::getEloquentQuery())
            ->withStandardRelations()
            ->applyAccessScopes()
            ->build();
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return SalaryIncreaseQueryBuilder::make(parent::getRecordRouteBindingEloquentQuery())
            ->withStandardRelations()
            ->applyAccessScopes()
            ->build()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function canViewAny(): bool
    {
        return isSuperAdmin() || isSystemManager() || isBranchManager() || isFinanceManager() || isHR() || isOwner();
    }

    public static function canEdit(Model $record): bool
    {
        return $record instanceof SalaryIncreaseRequest
            && $record->isPending()
            && static::canViewAny();
    }

    public static function canDelete(Model $record): bool
    {
        return $record instanceof SalaryIncreaseRequest
            && $record->isPending()
            && static::canViewAny();
    }

    public static function canForceDelete(Model $record): bool
    {
        return $record instanceof SalaryIncreaseRequest
            && $record->isPending()
            && static::canViewAny();
    }

    public static function canRestore(Model $record): bool
    {
        return $record instanceof SalaryIncreaseRequest
            && $record->isPending()
            && static::canViewAny();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }
}
