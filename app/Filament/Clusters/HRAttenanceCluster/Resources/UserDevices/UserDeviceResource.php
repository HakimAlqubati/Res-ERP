<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\UserDevices;

use App\Filament\Clusters\HRAttenanceCluster;
use App\Filament\Clusters\HRAttenanceCluster\Resources\UserDevices\Pages\CreateUserDevice;
use App\Filament\Clusters\HRAttenanceCluster\Resources\UserDevices\Pages\EditUserDevice;
use App\Filament\Clusters\HRAttenanceCluster\Resources\UserDevices\Pages\ListUserDevices;
use App\Filament\Clusters\HRAttenanceCluster\Resources\UserDevices\Pages\ViewUserDevice;
use App\Filament\Clusters\HRAttenanceCluster\Resources\UserDevices\Schemas\UserDeviceForm;
use App\Filament\Clusters\HRAttenanceCluster\Resources\UserDevices\Schemas\UserDeviceInfolist;
use App\Filament\Clusters\HRAttenanceCluster\Resources\UserDevices\Tables\UserDevicesTable;
use App\Models\UserDevice;
use BackedEnum;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class UserDeviceResource extends Resource
{
    protected static ?string $model = UserDevice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::DevicePhoneMobile;

    protected static ?string $cluster = HRAttenanceCluster::class;

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 7;
    protected static ?string $recordTitleAttribute = 'user';

    public static function form(Schema $schema): Schema
    {
        return UserDeviceForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return UserDeviceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UserDevicesTable::configure($table);
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
            'index' => ListUserDevices::route('/'),
            'create' => CreateUserDevice::route('/create'),
            'view' => ViewUserDevice::route('/{record}'),
            'edit' => EditUserDevice::route('/{record}/edit'),
        ];
    }
      public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
