<?php

declare(strict_types=1);

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\HolidayOccurrences;

use App\Filament\Clusters\HRAttenanceCluster\Resources\HolidayOccurrences\Pages\CreateHolidayOccurrence;
use App\Filament\Clusters\HRAttenanceCluster\Resources\HolidayOccurrences\Pages\EditHolidayOccurrence;
use App\Filament\Clusters\HRAttenanceCluster\Resources\HolidayOccurrences\Pages\ListHolidayOccurrences;
use App\Filament\Clusters\HRAttenanceCluster\Resources\HolidayOccurrences\Schemas\HolidayOccurrenceForm;
use App\Filament\Clusters\HRAttenanceCluster\Resources\HolidayOccurrences\Tables\HolidayOccurrencesTable;
use App\Filament\Clusters\HRLeaveManagementCluster;
use App\Models\HolidayOccurrence;
use App\Modules\HR\Holidays\Builders\HolidayOccurrenceQueryBuilder;
use BackedEnum;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class HolidayOccurrenceResource extends Resource
{
    protected static ?string $model = HolidayOccurrence::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $cluster = HRLeaveManagementCluster::class;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationLabel(): string
    {
        return 'Public Holidays';
    }

    public static function getPluralLabel(): ?string
    {
        return 'Holiday Occurrences';
    }

    public static function getModelLabel(): string
    {
        return 'Holiday Occurrence';
    }

    public static function getNavigationBadge(): ?string
    {
        $tentativeCount = static::getModel()::query()->tentative()->count();

        return $tentativeCount > 0
            ? "{$tentativeCount} Tentative"
            : (string) static::getModel()::query()->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::query()->tentative()->exists() ? 'warning' : 'primary';
    }

    public static function form(Schema $schema): Schema
    {
        return HolidayOccurrenceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HolidayOccurrencesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListHolidayOccurrences::route('/'),
            'create' => CreateHolidayOccurrence::route('/create'),
            'edit'   => EditHolidayOccurrence::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return HolidayOccurrenceQueryBuilder::make(parent::getEloquentQuery())
            ->withStandardRelations()
            ->withoutTrashedScope()
            ->orderedForCalendar()
            ->build();
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return HolidayOccurrenceQueryBuilder::make(parent::getRecordRouteBindingEloquentQuery())
            ->withStandardRelations()
            ->withoutTrashedScope()
            ->build();
    }

    public static function canViewAny(): bool
    {
        return isSuperAdmin() || isSystemManager() || isHR();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }
}
