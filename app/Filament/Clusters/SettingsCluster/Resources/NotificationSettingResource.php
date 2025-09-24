<?php

namespace App\Filament\Clusters\SettingsCluster\Resources;

use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Clusters\SettingsCluster\Resources\NotificationSettingResource\Pages\ListNotificationSettings;
use App\Filament\Clusters\SettingsCluster\Resources\NotificationSettingResource\Pages\CreateNotificationSetting;
use App\Filament\Clusters\SettingsCluster\Resources\NotificationSettingResource\Pages\EditNotificationSetting;
use App\Filament\Clusters\SettingsCluster;
use App\Filament\Clusters\SettingsCluster\Resources\NotificationSettingResource\Pages;
use App\Filament\Clusters\SettingsCluster\Resources\NotificationSettingResource\RelationManagers;
use App\Filament\Clusters\SettingsCluster\Resources\Tables\NotificationSettingTable;
use App\Models\NotificationSetting;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Fieldset;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Log;

class NotificationSettingResource extends Resource
{
    protected static ?string $model = NotificationSetting::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-bell-alert';

    protected static ?string $slug = 'notification-settings';
    // protected static ?string $cluster = SettingsCluster::class;
    // protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    // protected static ?int $navigationSort = 2;
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->label('Notification Type')
                    ->required()
                    ->options([
                        NotificationSetting::TYPE_STOCK_MIN_QUANTITY   => 'Stock Minimum Quantity',
                        NotificationSetting::TYPE_EMPLOYEE_FORGET      => 'Employee Forget Attendance',
                        NotificationSetting::TYPE_ABSENT_EMPLOYEES     => 'Absent Employees',
                        NotificationSetting::TYPE_TASK_SCHEDULING      => 'Task Scheduling',
                    ])
                    ->unique(ignoreRecord: true),

                Select::make('frequency')
                    ->label('Frequency')
                    ->required()
                    ->options([
                        'every_minute' => 'Every Minute',
                        'hourly'       => 'Hourly',
                        'daily'        => 'Daily',
                    ]),

                TimePicker::make('daily_time')
                    ->label('Daily Time')
                    ->seconds(false)
                    ->visible(fn($get) => $get('frequency') === 'daily'),

                Toggle::make('active')
                    ->label('Active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return NotificationSettingTable::configure($table);
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
            'index' => ListNotificationSettings::route('/'),
            'create' => CreateNotificationSetting::route('/create'),
            'edit' => EditNotificationSetting::route('/{record}/edit'),
        ];
    }
}
