<?php

namespace App\Filament\Resources\AppVersions\Tables;

use App\Models\AppVersion;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class AppVersionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->striped()
            ->defaultSort('version_code', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('platform')
                    ->label(__('Platform'))
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        AppVersion::PLATFORM_ANDROID => 'success',
                        AppVersion::PLATFORM_IOS     => 'info',
                        default                      => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        AppVersion::PLATFORM_ANDROID => 'Android',
                        AppVersion::PLATFORM_IOS     => 'iOS',
                        AppVersion::PLATFORM_ALL     => 'All Platforms',
                        default                      => ucfirst($state),
                    })
                    ->sortable()
                    ->searchable(),

                TextColumn::make('version_name')
                    ->label(__('Version Name'))
                    ->weight('bold')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('version_code')
                    ->label(__('Version Code'))
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('min_supported_version')
                    ->label(__('Min Version'))
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('min_version_code')
                    ->label(__('Min Code'))
                    ->placeholder('—')
                    ->alignCenter()
                    ->toggleable(),

                IconColumn::make('is_force_update')
                    ->label(__('Force Update'))
                    ->boolean()
                    ->alignCenter()
                    ->sortable(),

                ToggleColumn::make('is_active')
                    ->label(__('Active'))
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('download_url')
                    ->label(__('Download URL'))
                    ->limit(35)
                    ->url(fn(AppVersion $record): ?string => $record->download_url, shouldOpenInNewTab: true)
                    ->tooltip(fn(AppVersion $record): ?string => $record->download_url)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('creator.name')
                    ->label(__('Created By'))
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                SelectFilter::make('platform')
                    ->label(__('Platform'))
                    ->options([
                        AppVersion::PLATFORM_ALL     => __('All Platforms'),
                        AppVersion::PLATFORM_ANDROID => __('Android'),
                        AppVersion::PLATFORM_IOS     => __('iOS'),
                    ]),

                TernaryFilter::make('is_active')
                    ->label(__('Active Status')),

                TernaryFilter::make('is_force_update')
                    ->label(__('Force Update')),

                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }
}
