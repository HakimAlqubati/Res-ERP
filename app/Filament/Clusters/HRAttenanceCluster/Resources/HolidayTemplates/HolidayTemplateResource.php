<?php

declare(strict_types=1);

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\HolidayTemplates;

use App\Filament\Clusters\HRAttenanceCluster\Resources\HolidayTemplates\Pages\CreateHolidayTemplate;
use App\Filament\Clusters\HRAttenanceCluster\Resources\HolidayTemplates\Pages\EditHolidayTemplate;
use App\Filament\Clusters\HRAttenanceCluster\Resources\HolidayTemplates\Pages\ListHolidayTemplates;
use App\Filament\Clusters\HRAttenanceCluster\Resources\HolidayTemplates\Schemas\HolidayTemplateForm;
use App\Filament\Clusters\HRAttenanceCluster\Resources\HolidayTemplates\Tables\HolidayTemplatesTable;
use App\Filament\Clusters\HRLeaveManagementCluster;
use App\Models\HolidayTemplate;
use BackedEnum;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class HolidayTemplateResource extends Resource
{
    protected static ?string $model = HolidayTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    protected static ?string $cluster = HRLeaveManagementCluster::class;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationLabel(): string
    {
        return 'Public Holidays';
    }

    public static function getPluralLabel(): ?string
    {
        return 'Holiday Templates';
    }

    public static function getModelLabel(): string
    {
        return 'Holiday Template';
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::query()->count();
    }

    public static function form(Schema $schema): Schema
    {
        return HolidayTemplateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HolidayTemplatesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListHolidayTemplates::route('/'),
            'create' => CreateHolidayTemplate::route('/create'),
            'edit'   => EditHolidayTemplate::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['createdBy', 'confirmedBy'])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
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
