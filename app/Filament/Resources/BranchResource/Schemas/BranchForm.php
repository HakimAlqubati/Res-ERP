<?php

namespace App\Filament\Resources\BranchResource\Schemas;

use App\Models\Branch;
use App\Models\City;
use App\Models\Country;
use App\Models\District;
use App\Models\Store;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Illuminate\Support\Carbon;
use Filament\Schemas\Schema;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;


class BranchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Wizard::make([
                    Step::make('Basic data')
                        ->icon('heroicon-o-user-circle')
                        ->schema([
                            Fieldset::make()->columns(2)->schema([
                                TextInput::make('name')->required()->label(__('lang.name')),
                                Select::make('manager_id')
                                    ->label(__('lang.branch_manager'))
                                    ->options(User::whereHas('roles', function ($q) {
                                        $q->where('id', 7);
                                    })
                                        ->get(['name', 'id'])->pluck('name', 'id'))
                                    ->searchable(),
                                Grid::make()->columns(4)->columnSpanFull()->schema([

                                    //     ->label(__('lang.active')),
                                    // Select::make('type')
                                    //     ->label(__('lang.branch_type'))
                                    //     ->required()
                                    //     ->default(function () {
                                    //         // 🧠 التقاط قيمة type من URL تلقائياً (activeTab)
                                    //         $tab = request()->get('activeTab');
                                    //         return in_array($tab, Branch::TYPES) ? $tab : Branch::TYPE_BRANCH;
                                    //     })
                                    //     ->options([
                                    //         Branch::TYPE_BRANCH => __('lang.normal_branch'),
                                    //         Branch::TYPE_CENTRAL_KITCHEN => __('lang.central_kitchen'),
                                    //         Branch::TYPE_HQ => __('lang.hq'),
                                    //         Branch::TYPE_POPUP => __('lang.popup_branch'),
                                    //     ])
                                    //     ->default(Branch::TYPE_BRANCH)

                                    //     ->reactive(),
                                    ToggleButtons::make('type')->required()
                                        ->default(function () {
                                            $tab = request()->get('activeTab');
                                            return in_array($tab, Branch::TYPES) ? $tab : Branch::TYPE_BRANCH;
                                        })->options([
                                            Branch::TYPE_BRANCH => __('lang.normal_branch'),
                                            // Branch::TYPE_CENTRAL_KITCHEN => __('lang.central_kitchen'),
                                            Branch::TYPE_HQ => __('lang.hq'),
                                            Branch::TYPE_POPUP => __('lang.popup_branch'),
                                            // Branch::TYPE_RESELLER => __('lang.reseller'),
                                        ])->columns(4)
                                        ->default(Branch::TYPE_BRANCH)
                                        ->icons([
                                            Branch::TYPE_BRANCH => 'heroicon-o-building-storefront',
                                            // Branch::TYPE_CENTRAL_KITCHEN => 'heroicon-o-fire',
                                            Branch::TYPE_HQ => 'heroicon-o-building-storefront',
                                            Branch::TYPE_POPUP => 'heroicon-o-sparkles',
                                            // Branch::TYPE_RESELLER => 'heroicon-o-user-group',
                                        ])
                                        ->colors([
                                            Branch::TYPE_BRANCH => 'warning',
                                            // Branch::TYPE_CENTRAL_KITCHEN => 'info',
                                            Branch::TYPE_HQ => 'success',
                                            Branch::TYPE_POPUP => 'danger',
                                            // Branch::TYPE_RESELLER => 'info',
                                        ])
                                        ->inline()
                                        ->reactive()->columnSpan(3),
                                    Toggle::make('active')
                                        ->inline(false)->default(true),
                                    Grid::make()->columnSpanFull()->columns(3)->schema([
                                        // Toggle::make('manager_abel_show_orders')
                                        //     ->label(__('stock.manager_abel_show_orders'))
                                        //     ->inline(false)
                                        //     ->default(false)
                                        //     ->visible(fn(callable $get) => $get('type') === Branch::TYPE_CENTRAL_KITCHEN),

                                        Select::make('store_id')
                                            ->label(__('stock.store_id'))
                                            ->options(Store::active()
                                                ->centralKitchen()->pluck('name', 'id'))
                                            ->searchable()
                                        // ->requiredIf('type', Branch::TYPE_CENTRAL_KITCHEN)
                                        // ->visible(fn(callable $get) => $get('type') === Branch::TYPE_CENTRAL_KITCHEN)
                                        ,
                                        // Select::make('categories')
                                        //     ->label(__('stock.customized_manufacturing_categories'))
                                        //     // ->options(\App\Models\Category::Manufacturing()->pluck('name', 'id'))
                                        //     ->relationship('categories', 'name')

                                        //     ->searchable()->multiple()
                                        //     ->visible(fn(callable $get) => $get('type') === Branch::TYPE_CENTRAL_KITCHEN),

                                    ]),

                                ]),
                                Fieldset::make()->columns(2)
                                    ->visible(fn(callable $get) => $get('type') === Branch::TYPE_POPUP)
                                    ->label('Set Start and End Date for Popup Branch')
                                    ->schema([
                                        DateTimePicker::make('start_date')
                                            ->default(now()->addDay())

                                            ->label(__('lang.start_date'))
                                            ->required(fn(callable $get) => $get('type') === Branch::TYPE_POPUP)
                                            ->visible(fn(callable $get) => $get('type') === Branch::TYPE_POPUP)
                                            ->live()
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                if ($state) {
                                                    $newEndDate = Carbon::parse($state)->addDay();
                                                    $set('end_date', $newEndDate);
                                                }
                                            }),

                                        DateTimePicker::make('end_date')
                                            ->label(__('lang.end_date'))
                                            ->default(now()->addDays(2))

                                            ->required(fn(callable $get) => $get('type') === Branch::TYPE_POPUP)
                                            ->after('start_date')
                                            ->visible(fn(callable $get) => $get('type') === Branch::TYPE_POPUP),
                                        Textarea::make('more_description')
                                            ->label(__('lang.more_description'))
                                            ->rows(3)->columnSpanFull()
                                            ->nullable()
                                            ->visible(fn(callable $get) => $get('type') === Branch::TYPE_POPUP),
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
                                SpatieMediaLibraryFileUpload::make('images')
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
                                    ])->maxSize(2000)
                                    ->imageEditorMode(2)
                                    ->imageEditorEmptyFillColor('#fff000')
                                    ->circleCropper()
                            ])
                        ]),
                ])->columnSpanFull()->skippable(),

            ]);
    }
}
