<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\UserDevices\Tables;

use App\Models\UserDevice;
use App\Filament\Clusters\HRAttenanceCluster\Resources\UserDevices\UserDeviceResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class UserDevicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->striped()
            ->recordUrl(fn(UserDevice $record): string => UserDeviceResource::getUrl('view', ['record' => $record]))

            ->columns([
                TextColumn::make('user.name')
                    ->label('User')
                    ->sortable()
                    ->searchable()->toggleable(),

                BadgeColumn::make('plat_form')
                    ->label('Platform')
                    ->colors([
                        'success' => 'android',
                        'info' => 'ios',
                        'warning' => 'web',
                        'gray' => 'desktop',
                    ])->toggleable()
                    ->sortable(),

                TextColumn::make('device_hash')
                    ->label('Device Hash')->toggleable()
                    ->copyable()
                    ->limit(20)
                    ->tooltip(fn($record) => $record->device_hash),
                TextColumn::make('branch.name')
                    ->label('Branch')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('branchArea.name')
                    ->label('Area')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->placeholder('-'),
                ToggleColumn::make('active')->toggleable()
                    ->label('Active')->alignCenter(),

                // TextColumn::make('last_login')
                //     ->label('Last Login')
                //     ->dateTime('Y-m-d H:i')
                //     ->sortable(),

                TextColumn::make('notes')
                    ->label('Notes')->toggleable()
                    ->limit(40)
                    ->wrap(),
            ])
            ->filters([])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
