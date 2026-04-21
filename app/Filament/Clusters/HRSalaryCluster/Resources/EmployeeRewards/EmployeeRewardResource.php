<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources\EmployeeRewards;

use App\Filament\Clusters\HRSalaryCluster;
use App\Filament\Clusters\HRSalaryCluster\Resources\EmployeeRewards\Pages\CreateEmployeeReward;
use App\Filament\Clusters\HRSalaryCluster\Resources\EmployeeRewards\Pages\EditEmployeeReward;
use App\Filament\Clusters\HRSalaryCluster\Resources\EmployeeRewards\Pages\ListEmployeeRewards;
use App\Filament\Clusters\HRSalaryCluster\Resources\EmployeeRewards\Schemas\EmployeeRewardForm;
use App\Filament\Clusters\HRSalaryCluster\Resources\EmployeeRewards\Tables\EmployeeRewardsTable;
use App\Models\EmployeeReward;
use BackedEnum;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EmployeeRewardResource extends Resource
{
    protected static ?string $model = EmployeeReward::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static ?string $cluster = HRSalaryCluster::class;
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'reason';

    public static function form(Schema $schema): Schema
    {
        return EmployeeRewardForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmployeeRewardsTable::configure($table);
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
            'index' => ListEmployeeRewards::route('/'),
            'create' => CreateEmployeeReward::route('/create'),
            'edit' => EditEmployeeReward::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
