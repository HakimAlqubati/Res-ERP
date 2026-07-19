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
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EmployeeServiceTerminationResource extends Resource
{
    protected static ?string $model = EmployeeServiceTermination::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserMinus;

    protected static ?string $cluster = HRCluster::class;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 2;
    protected static ?string $recordTitleAttribute = 'employee';

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

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
