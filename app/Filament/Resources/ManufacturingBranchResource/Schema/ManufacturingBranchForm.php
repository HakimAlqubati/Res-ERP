<?php

namespace App\Filament\Resources\ManufacturingBranchResource\Schema;

use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Components\Fieldset;
 
use App\Models\Branch;
use App\Models\City;
use App\Models\Country;
use App\Models\District;
use App\Models\User;  
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
 

class ManufacturingBranchForm
{

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Wizard::make([
                    Step::make('Basic data')
                        ->icon('heroicon-o-user-circle')
                        ->schema([
                            Fieldset::make()->columns(3)->schema([
                                TextInput::make('name')->required()->label(__('lang.name')),
                                Select::make('manager_id')
                                    ->label(__('lang.account_manager'))
                                    ->options(function (?Branch $record) {
                                        if (! $record?->id) {
                                            return User::pluck('name', 'id');
                                        }

                                        return $record->allUsers()->pluck('name', 'id');
                                    })
                                    ->searchable(),
                                Toggle::make('active')
                                    ->inline(false)->default(true),

                                Grid::make()->columnSpanFull()->columns(3)->schema([
                                    Toggle::make('manager_abel_show_orders')
                                        ->label(__('stock.manager_abel_show_orders'))
                                        ->inline(false)
                                        ->default(false),

                                    Select::make('store_id')
                                        ->label(__('stock.store_id'))
                                        // ->options(Store::active()
                                        //     ->centralKitchen()->pluck('name', 'id'))
                                        ->relationship('store', 'name')
                                        ->searchable(),
                                    Select::make('categories')
                                        ->label(__('stock.customized_manufacturing_categories'))
                                        // ->options(\App\Models\Category::Manufacturing()->pluck('name', 'id'))
                                        ->relationship('categories', 'name')

                                        ->searchable()->multiple(),

                                ]),
                                Textarea::make('address')
                                    ->columnSpanFull()
                                    ->label(__('lang.address')),
                            ]),

                        ]),
                    Step::make('Location')
                        ->icon('heroicon-o-map-pin')
                        ->schema([
                            Fieldset::make()
                                ->relationship('location')
                                ->columns(3)->schema([
                                    Select::make('country_id')
                                        ->label(__('Country'))->searchable()
                                        // ->relationship('city', 'name')
                                        ->options(Country::get(['id', 'name'])->pluck('name', 'id'))
                                        ->reactive()
                                        ->required(false),
                                    Select::make('city_id')
                                        ->label(__('City'))->searchable()
                                        // ->relationship('city', 'name')
                                        ->options(function (callable $get) {
                                            $countryId = $get('country_id');
                                            return $countryId ? City::where('country_id', $countryId)->pluck('name', 'id') : [];
                                        })
                                        ->reactive()
                                        ->required(false),

                                    Select::make('district_id')
                                        ->label(__('District'))
                                        ->searchable()
                                        ->options(function (callable $get) {
                                            $cityId = $get('city_id');
                                            return $cityId ? District::where('city_id', $cityId)->pluck('name', 'id') : [];
                                        })
                                        ->reactive()
                                        ->required(false),
                                    Textarea::make('address')->label(__('lang.address'))->columnSpanFull(),
                                ]),

                        ]),
                    Step::make('Images')
                        ->icon('heroicon-o-user-circle')
                        ->schema([
                            Fieldset::make()->columns(1)->schema([
                                FileUpload::make('images')
                                    ->disk('public')
                                    ->label('')
                                    ->directory('branches')
                                    ->columnSpanFull()
                                    ->image()
                                    ->multiple()
                                    ->downloadable()
                                    ->moveFiles()
                                    ->previewable()
                                    ->imagePreviewHeight('250')
                                    ->loadingIndicatorPosition('right')
                                    ->panelLayout('integrated')
                                    ->removeUploadedFileButtonPosition('right')
                                    ->uploadButtonPosition('right')
                                    ->uploadProgressIndicatorPosition('right')
                                    ->panelLayout('grid')
                                    ->reorderable()
                                    ->openable()
                                    ->downloadable(true)
                                    ->previewable(true)
                                    ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                                        return (string) str($file->getClientOriginalName())->prepend('branch-');
                                    })
                                    ->imageEditor()
                                    ->imageEditorAspectRatios([
                                        '16:9',
                                        '4:3',
                                        '1:1',
                                    ])->maxSize(800)
                                    ->imageEditorMode(2)
                                    ->imageEditorEmptyFillColor('#fff000')
                                    ->circleCropper(),
                            ]),
                        ]),
                ])->columnSpanFull()->skippable(),

            ]);
    }
}
