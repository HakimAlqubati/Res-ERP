<?php

namespace App\Filament\Clusters\POSIntegration\Resources\PosImportData;

use App\Filament\Clusters\POSIntegration\POSIntegrationCluster;
use App\Filament\Clusters\POSIntegration\Resources\PosImportData\Pages\CreatePosImportData;
use App\Filament\Clusters\POSIntegration\Resources\PosImportData\Pages\EditPosImportData;
use App\Filament\Clusters\POSIntegration\Resources\PosImportData\Pages\ListPosImportData;
use App\Filament\Clusters\POSIntegration\Resources\PosImportData\Schemas\PosImportDataForm;
use App\Filament\Clusters\POSIntegration\Resources\PosImportData\Tables\PosImportDataTable;
use App\Models\PosImportData;
use BackedEnum;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PosImportDataResource extends Resource
{
    protected static ?string $model = PosImportData::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ArrowDownCircle;

    protected static ?string $cluster = POSIntegrationCluster::class;
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort                         = 1;
    protected static ?string $recordTitleAttribute = 'branch';


    protected static ?string $label = 'POS Import Data';
    protected static ?string $pluralLabel = 'POS Import Data';
    protected static bool $shouldRegisterNavigation = false;

    // public static function form(Schema $schema): Schema
    // {
    //     return PosImportDataForm::configure($schema);
    // }

    public static function table(Table $table): Table
    {
        return PosImportDataTable::configure($table);
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
            'index' => ListPosImportData::route('/'),
            // 'create' => CreatePosImportData::route('/create'),
            // 'edit' => EditPosImportData::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
