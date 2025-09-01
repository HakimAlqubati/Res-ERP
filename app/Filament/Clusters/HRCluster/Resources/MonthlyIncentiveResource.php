<?php

namespace App\Filament\Clusters\HRCluster\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Clusters\HRCluster\Resources\MonthlyIncentiveResource\Pages\ListMonthlyIncentives;
use App\Filament\Clusters\HRCluster\Resources\MonthlyIncentiveResource\Pages\CreateMonthlyIncentive;
use App\Filament\Clusters\HRCluster\Resources\MonthlyIncentiveResource\Pages\EditMonthlyIncentive;
use App\Filament\Clusters\HRCluster\Resources\MonthlyIncentiveResource\Pages\ViewMonthlyIncentive;
use App\Filament\Clusters\HRCluster;
use App\Filament\Clusters\HRCluster\Resources\MonthlyIncentiveResource\Pages;
use App\Filament\Clusters\HRCluster\Resources\MonthlyIncentiveResource\RelationManagers;
use App\Filament\Clusters\HRSalaryCluster;
use App\Filament\Clusters\HRSalarySettingCluster;
use App\Models\MonthlyIncentive;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MonthlyIncentiveResource extends Resource
{
    protected static ?string $model = MonthlyIncentive::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::PlusCircle;

    protected static ?string $cluster = HRSalarySettingCluster::class;
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 7;

    public static function getModelLabel(): string
    {
        return 'Bonus';
    }
    public static function getPluralLabel(): ?string
    {
        return 'Bonus';
    }
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->required(),
                Textarea::make('description'),
                Toggle::make('active')->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->sortable()->searchable(),
                TextColumn::make('description'),
                // Tables\Columns\IconColumn::make('active'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => ListMonthlyIncentives::route('/'),
            'create' => CreateMonthlyIncentive::route('/create'),
            'edit' => EditMonthlyIncentive::route('/{record}/edit'),
            'view' => ViewMonthlyIncentive::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function canViewAny(): bool
    {
        if (isSuperAdmin() || isSystemManager() || isBranchManager() || isFinanceManager()) {
            return true;
        }
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (isSuperAdmin() ||  isSystemManager()) {
            return true;
        }
        return false;
    }

    public static function canCreate(): bool
    {

        if (isSystemManager()  || isSuperAdmin()) {
            return true;
        }
        return false;
    }
}
