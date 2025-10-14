<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\UserDevices\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\Fieldset;

class UserDeviceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Fieldset::make('Device Information')
                ->schema([
                    Select::make('user_id')
                        ->label('User')
                        ->relationship('user', 'name')
                        ->searchable()
                        ->preload()
                        // ->hiddenOn('create')
                        ,

                    TextInput::make('device_hash')
                        ->label('Device Hash (SHA-256)')
                        // ->disabled()
                        // ->dehydrated(false)
                        ->copyable(),

                    Select::make('plat_form')
                        ->label('Platform')
                        ->options([
                            'android' => 'Android',
                        ])
                        ->native(false)
                        ->searchable()->default('android')
                        ->placeholder('Select platform')
                        ->columnSpan(1)
                        ->disabled(),

                    Toggle::make('active')
                        ->label('Active')
                        ->inline(false),

                    DateTimePicker::make('last_login')
                        ->label('Last Login')
                        ->seconds(false)
                        ->displayFormat('Y-m-d H:i')
                        ->disabled()
                        ->hiddenOn('create')
                        ->dehydrated(false)
                        ->columnSpan(2),
                ])
                ->columns(4)
                ->columnSpanFull(),

            Fieldset::make('Notes')
                ->schema([
                    Textarea::make('notes')
                        ->label('Notes')
                        ->placeholder('Enter any notes...')
                        ->rows(4)
                        ->autosize()
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ]);
    }
}
