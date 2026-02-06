<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;


use App\Filament\Resources\BranchResource\Pages\ManageBranches;
use App\Filament\Resources\BranchResource\Pages\EditBranch;
use App\Filament\Resources\BranchResource\Pages\CreateBranch;
use App\Filament\Resources\BranchResource\Pages;
use App\Filament\Resources\BranchResource\Pages\ViewBranch;
use App\Filament\Resources\BranchResource\RelationManagers\AreasRelationManager;
use App\Filament\Resources\BranchResource\Schemas\BranchForm;
use App\Filament\Resources\BranchResource\Tables\BranchTable;
use App\Models\Branch;

use Filament\Resources\Resource;
 
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BranchResource extends Resource
{
    protected static ?string $model = Branch::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-building-office-2';
    protected static string | \UnitEnum | null $navigationGroup = 'Branches';
    public static function getNavigationLabel(): string
    {
        return __('lang.branches');
    }
    public static function form(Schema $schema): Schema
    {
        return BranchForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BranchTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageBranches::route('/'),
            'edit' => EditBranch::route('/{record}/edit'),
            'view' => ViewBranch::route('/{record}'),
            'create' => CreateBranch::route('/create'),


        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::branches()
            ->forBranchManager('id')
            ->count();
    }

    public static function getRelations(): array
    {
        return [
            AreasRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            // ->whereIn('type', [Branch::TYPE_BRANCH])
            ->forBranchManager('id')
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
    public static function canViewAny(): bool
    {
        return true;
    }

    public static function canCreate(): bool
    {
        if (isSuperAdmin() || isSystemManager()) {
            return true;
        }
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (isSuperAdmin() || isSystemManager()) {
            return true;
        }
        return false;
    }

    public static function canDeleteAny(): bool
    {
        if (isSuperAdmin() || isSystemManager()) {
            return true;
        }
        return false;
    }
}
