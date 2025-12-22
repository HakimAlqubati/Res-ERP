<?php

namespace App\Filament\Clusters\HRServiceRequestCluster\Resources;

use App\Filament\Clusters\HRServiceRequestCluster;
use App\Filament\Clusters\HRServiceRequestCluster\Resources\ServiceRequestResource\Forms\ServiceRequestForm;
use App\Filament\Clusters\HRServiceRequestCluster\Resources\ServiceRequestResource\Pages\CreateServiceRequest;
use App\Filament\Clusters\HRServiceRequestCluster\Resources\ServiceRequestResource\Pages\EditServiceRequest;
use App\Filament\Clusters\HRServiceRequestCluster\Resources\ServiceRequestResource\Pages\ListServiceRequests;
use App\Filament\Clusters\HRServiceRequestCluster\Resources\ServiceRequestResource\Pages\ViewServiceRequest;
use App\Filament\Clusters\HRServiceRequestCluster\Resources\ServiceRequestResource\RelationManagers\CommentsRelationManager;
use App\Filament\Clusters\HRServiceRequestCluster\Resources\ServiceRequestResource\RelationManagers\CostsRelationManager;
use App\Filament\Clusters\HRServiceRequestCluster\Resources\ServiceRequestResource\RelationManagers\LogsRelationManager;
use App\Filament\Clusters\HRServiceRequestCluster\Resources\ServiceRequestResource\Tables\ServiceRequestTable;
use App\Models\ServiceRequest;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ServiceRequestResource extends Resource
{
    protected static ?string $model = ServiceRequest::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::WrenchScrewdriver;

    protected static ?string $cluster = HRServiceRequestCluster::class;

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 1;

    /**
     * Configure the form schema
     */
    public static function form(Schema $schema): Schema
    {
        return ServiceRequestForm::configure($schema);
    }

    /**
     * Configure the table
     */
    public static function table(Table $table): Table
    {
        return ServiceRequestTable::configure($table);
    }

    /**
     * Get relation managers
     */
    public static function getRelations(): array
    {
        return [
            CommentsRelationManager::class,
            LogsRelationManager::class,
            CostsRelationManager::class,
        ];
    }

    /**
     * Get resource pages
     */
    public static function getPages(): array
    {
        return [
            'index'  => ListServiceRequests::route('/'),
            'create' => CreateServiceRequest::route('/create'),
            'edit'   => EditServiceRequest::route('/{record}/edit'),
            'view'   => ViewServiceRequest::route('/{record}'),
        ];
    }

    /**
     * Get record sub navigation
     */
    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            ListServiceRequests::class,
            CreateServiceRequest::class,
            EditServiceRequest::class,
            ViewServiceRequest::class,
        ]);
    }

    /**
     * Get navigation badge count
     */
    public static function getNavigationBadge(): ?string
    {
        if (auth()->user()->is_branch_manager) {
            return static::getModel()::where('branch_id', auth()->user()->branch->id)->count();
        }
        return static::getModel()::count();
    }

    /**
     * Get eloquent query with branch manager scope
     */
    public static function getEloquentQuery(): Builder
    {
        $query = static::getModel();
        if (auth()->user()->is_branch_manager) {
            return $query::query()->where('branch_id', auth()->user()->branch->id);
        }
        return $query::query()->forBranchManager();
    }

    /**
     * Check if record can be deleted
     */
    public static function canDelete(Model $record): bool
    {
        if (isMaintenanceManager() || isSystemManager() || isSuperAdmin() || isBranchManager()) {
            return true;
        }
        return false;
    }

    /**
     * Check if user can create records
     */
    public static function canCreate(): bool
    {
        if (isFinanceManager()) {
            return false;
        }
        return true;
    }

    /**
     * Get media spatie field - kept for backward compatibility
     * @deprecated Use ServiceRequestForm::getMediaField() instead
     */
    public static function getMediaSpatieField()
    {
        return ServiceRequestForm::getMediaField();
    }
}
