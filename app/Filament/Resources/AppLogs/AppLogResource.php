<?php

namespace App\Filament\Resources\AppLogs;

use App\Filament\Resources\AppLogs\Pages\CreateAppLog;
use App\Filament\Resources\AppLogs\Pages\EditAppLog;
use App\Filament\Resources\AppLogs\Pages\ListAppLogs;
use App\Filament\Resources\AppLogs\Pages\ViewAppLog;
use App\Filament\Resources\AppLogs\Schemas\AppLogForm;
use App\Filament\Resources\AppLogs\Schemas\AppLogInfolist;
use App\Filament\Resources\AppLogs\Tables\AppLogsTable;
use App\Models\AppLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AppLogResource extends Resource
{
    protected static ?string $model = AppLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::QueueList;

    protected static ?string $recordTitleAttribute = 'level';

    public static function form(Schema $schema): Schema
    {
        return AppLogForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AppLogInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AppLogsTable::configure($table);
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
            'index' => ListAppLogs::route('/'),
            'view' => ViewAppLog::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
