<?php

namespace App\Filament\Resources\AppVersions\Schemas;

use App\Models\AppVersion;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AppVersionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Version Information'))
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(3)->schema([
                            Select::make('platform')
                                ->label(__('Platform'))
                                ->options([
                                    AppVersion::PLATFORM_ALL     => __('All Platforms'),
                                    AppVersion::PLATFORM_ANDROID => __('Android'),
                                    AppVersion::PLATFORM_IOS     => __('iOS'),
                                ])
                                ->default(AppVersion::PLATFORM_ANDROID)
                                ->disabled()
                                ->dehydrated()
                                ->required()
                                ->native(false)
                                ->columnSpan(1),

                            TextInput::make('version_name')
                                ->label(__('Version Name'))
                                ->placeholder('e.g. 1.0.5')
                                ->required()
                                ->maxLength(50)
                                ->columnSpan(1),

                            TextInput::make('version_code')
                                ->label(__('Version Code'))
                                ->placeholder('e.g. 105')
                                ->numeric()
                                ->minValue(1)
                                ->required()
                                ->columnSpan(1),
                        ]),

                        Grid::make(2)->schema([
                            TextInput::make('min_supported_version')
                                ->label(__('Min Supported Version'))
                                ->placeholder('e.g. 1.0.0')
                                ->helperText(__('Lowest version name allowed before requiring update'))
                                ->maxLength(50)
                                ->columnSpan(1),

                            TextInput::make('min_version_code')
                                ->label(__('Min Version Code'))
                                ->placeholder('e.g. 100')
                                ->helperText(__('Lowest version code allowed before requiring update'))
                                ->numeric()
                                ->minValue(1)
                                ->columnSpan(1),
                        ]),

                        Grid::make(2)->schema([
                            Toggle::make('is_force_update')
                                ->label(__('Force Update'))
                                ->helperText(__('Prevent users on outdated versions from using the app'))
                                ->default(false)
                                ->inline(false)
                                ->columnSpan(1),

                            Toggle::make('is_active')
                                ->label(__('Active'))
                                ->helperText(__('Enable or disable this version check'))
                                ->default(true)
                                ->inline(false)
                                ->columnSpan(1),
                        ]),

                        TextInput::make('download_url')
                            ->label(__('Download URL / Store Link'))
                            ->placeholder('https://play.google.com/store/apps/details?id=... or App Store URL')
                            ->url()
                            ->maxLength(500)
                            ->columnSpanFull(),

                        Textarea::make('release_notes')
                            ->label(__('Release Notes'))
                            ->placeholder(__('Detail new features, bug fixes, or enhancements in this release...'))
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
