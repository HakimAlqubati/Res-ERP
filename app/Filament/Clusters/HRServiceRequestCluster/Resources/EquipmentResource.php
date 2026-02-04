<?php

namespace App\Filament\Clusters\HRServiceRequestCluster\Resources;

use App\Filament\Clusters\HRServiceRequestCluster;
use App\Filament\Clusters\HRServiceRequestCluster\Resources\EquipmentResource\Forms\EquipmentForm;
use App\Filament\Clusters\HRServiceRequestCluster\Resources\EquipmentResource\Pages\CreateEquipment;
use App\Filament\Clusters\HRServiceRequestCluster\Resources\EquipmentResource\Pages\EditEquipment;
use App\Filament\Clusters\HRServiceRequestCluster\Resources\EquipmentResource\Pages\ListEquipment;
use App\Filament\Clusters\HRServiceRequestCluster\Resources\EquipmentResource\Pages\ViewEquipment;
use App\Filament\Clusters\HRServiceRequestCluster\Resources\EquipmentResource\RelationManagers\EquipmentLogsRelationManager;
use App\Filament\Clusters\HRServiceRequestCluster\Resources\EquipmentResource\Services\EquipmentCodeGenerator;
use App\Filament\Clusters\HRServiceRequestCluster\Resources\EquipmentResource\Tables\EquipmentTable;
use App\Models\Equipment;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EquipmentResource extends Resource
{
    protected static ?string $model = Equipment::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::Wrench;

    protected static ?string $cluster = HRServiceRequestCluster::class;

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 2;

    /**
     * Configure the form schema
     */
    public static function form(Schema $schema): Schema
    {
        return EquipmentForm::configure($schema);
    }

    /**
     * Configure the table
     */
    public static function table(Table $table): Table
    {
        return EquipmentTable::configure($table);
    }

    /**
     * Get relation managers
     */
    public static function getRelations(): array
    {
        return [
            EquipmentLogsRelationManager::class,
        ];
    }

    /**
     * Get resource pages
     */
    public static function getPages(): array
    {
        return [
            'index'  => ListEquipment::route('/'),
            'create' => CreateEquipment::route('/create'),
            'edit'   => EditEquipment::route('/{record}/edit'),
            'view'   => ViewEquipment::route('/{record}'),
        ];
    }

    /**
     * Get record sub navigation
     */
    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            ListEquipment::class,
            CreateEquipment::class,
            EditEquipment::class,
            ViewEquipment::class,
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
        return static::getModel()::forBranchManager()->count();
    }

    /**
     * Generate equipment code - kept for backward compatibility
     * @deprecated Use EquipmentCodeGenerator::generate() instead
     */
    public static function generateEquipmentCode(?int $typeId): string
    {
        return EquipmentCodeGenerator::generate($typeId);
    }

    /**
     * Get eloquent query with branch manager scope
     */
    public static function getEloquentQuery(): Builder
    {
        return static::getModel()::query()->forBranchManager();
    }
}
