<?php

namespace App\Filament\Resources\ApprovalPolicies;

use App\Filament\Clusters\HRCluster;
use App\Filament\Clusters\HRUserTypeCluster;
use App\Filament\Resources\ApprovalPolicies\Pages\CreateApprovalPolicy;
use App\Filament\Resources\ApprovalPolicies\Pages\EditApprovalPolicy;
use App\Filament\Resources\ApprovalPolicies\Pages\ListApprovalPolicies;
use App\Filament\Resources\ApprovalPolicies\Schemas\ApprovalPolicyForm;
use App\Filament\Resources\ApprovalPolicies\Tables\ApprovalPoliciesTable;
use App\Modules\HR\ApprovalPolicies\Models\ApprovalPolicy;
use BackedEnum;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ApprovalPolicyResource extends Resource
{
    protected static ?string $model = ApprovalPolicy::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $cluster = HRUserTypeCluster::class;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('Workflow Policies');
    }

    public static function getModelLabel(): string
    {
        return __('Workflow Policy');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Workflow Policies');
    }

    public static function form(Schema $schema): Schema
    {
        return ApprovalPolicyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ApprovalPoliciesTable::configure($table);
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
            'index' => ListApprovalPolicies::route('/'),
            'create' => CreateApprovalPolicy::route('/create'),
            'edit' => EditApprovalPolicy::route('/{record}/edit'),
        ];
    }
    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            ListApprovalPolicies::class,
            CreateApprovalPolicy::class,
            EditApprovalPolicy::class,
        ]);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }


    public static function canCreate(): bool
    {
        return isSuperAdmin() || isSystemManager();
    }

    public static function canDelete(Model $record): bool
    {
        return isSuperAdmin() || isSystemManager();
    }

    public static function canDeleteAny(): bool
    {
        return isSuperAdmin() || isSystemManager();
    }

    public static function canEdit(Model $record): bool
    {
        return isSuperAdmin() || isSystemManager();
    }

    public static function canViewAny(): bool
    {
        return isSuperAdmin() || isSystemManager();
    }
}
