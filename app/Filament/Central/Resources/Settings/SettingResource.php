<?php

declare(strict_types=1);

namespace App\Filament\Central\Resources\Settings;

use App\Filament\Central\Resources\Settings\Pages\CreateSetting;
use App\Filament\Central\Resources\Settings\Schemas\SettingForm;
use App\Models\CentralSetting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class SettingResource extends Resource
{
    protected static ?string $model = CentralSetting::class;

    protected static ?string $slug = 'settings';

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string | UnitEnum | null $navigationGroup = null;

    protected static ?int $navigationSort = 5;

    public static function getNavigationLabel(): string
    {
        return 'Settings';
    }

    public static function getModelLabel(): string
    {
        return 'Central Setting';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Central Settings';
    }

    public static function form(Schema $schema): Schema
    {
        return SettingForm::configure($schema);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => CreateSetting::route('/'),
        ];
    }
}
