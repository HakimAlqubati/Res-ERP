<?php

namespace App\Filament\Clusters\HRServiceRequestCluster\Resources\EquipmentResource\Forms;

use App\Filament\Clusters\HRServiceRequestCluster\Resources\EquipmentResource\Services\EquipmentCodeGenerator;
use App\Models\Branch;
use App\Models\BranchArea;
use App\Models\Equipment;
use App\Models\EquipmentType;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class EquipmentForm
{
    /**
     * Configure the form schema
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    static::getBasicDataStep(),
                    static::getDatesStep(),
                    static::getImagesStep(),
                ])->skippable()->columnSpanFull(),
            ]);
    }

    /**
     * Step 1: Basic Data - البيانات الأساسية
     */
    public static function getBasicDataStep(): Step
    {
        return Step::make('Basic data')
            ->icon('heroicon-o-bars-3-center-left')
            ->schema([
                Fieldset::make()->columnSpanFull()->schema([
                    // Name & Status Grid
                    Grid::make()->columnSpanFull()->columns(2)->schema([
                        TextInput::make('name')
                            ->label('Name')
                            ->required()->prefixIconColor('primary')->columnSpan(1)
                            ->unique(ignoreRecord: true)->prefixIcon('heroicon-s-information-circle'),
                        Select::make('status')
                            ->label('Status')->required()
                            ->options(Equipment::STATUS_LABELS)->default(Equipment::STATUS_ACTIVE)
                            ->prefixIcon('heroicon-s-chart-bar-square')->prefixIconColor('primary'),

                        Select::make('type_id')
                            ->label('Type')->searchable()
                            ->options(EquipmentType::active()->pluck('name', 'id'))
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $set('asset_tag', EquipmentCodeGenerator::generate($state));
                            })
                            ->prefixIcon('heroicon-s-ellipsis-horizontal')->prefixIconColor('primary'),

                        TextInput::make('asset_tag')
                            ->label('Asset Tag')->prefixIconColor('primary')
                            ->required()->prefixIconColor('primary')->readOnly()
                            ->unique(ignoreRecord: true)->prefixIcon('heroicon-s-tag'),
                        TextInput::make('qr_code')->prefixIcon('heroicon-s-qr-code')->prefixIconColor('primary')
                            ->label('QR Code')
                            ->required()
                            ->unique(ignoreRecord: true)->hidden(),
                    ]),

                    // Branch & Area Fieldset
                    Fieldset::make()->columnSpanFull()->label('Set Branch & Area')->columns(2)->schema([
                        Select::make('branch_id')
                            ->label('Branch')->searchable()
                            ->options(Branch::selectable()
                                ->forBranchManager('id')
                                ->select('id', 'name')
                                ->get()
                                ->pluck('name', 'id'))
                            ->required()->live()->prefixIcon('heroicon-s-ellipsis-horizontal')->prefixIconColor('primary'),
                        Select::make('branch_area_id')
                            ->required()
                            ->label('Branch area')
                            ->options(function ($get) {
                                return BranchArea::query()
                                    ->where('branch_id', $get('branch_id'))
                                    ->pluck('name', 'id');
                            })->prefixIcon('heroicon-s-ellipsis-horizontal')->prefixIconColor('primary'),
                    ]),

                    // Serial, Make, Model Grid
                    Grid::make()->columnSpanFull()->columns(3)->schema([
                        TextInput::make('serial_number')
                            ->label('Serial Number')->prefixIcon('heroicon-s-ellipsis-vertical')->prefixIconColor('primary')
                            ->unique(ignoreRecord: true),
                        TextInput::make('make')
                            ->label('Make')
                            ->nullable()
                            ->prefixIcon('heroicon-s-bookmark-square')->prefixIconColor('primary'),

                        TextInput::make('model')
                            ->label('Model')
                            ->prefixIcon('heroicon-s-wallet')->prefixIconColor('primary')
                            ->nullable(),
                    ]),

                    // Price & Size Grid
                    Grid::make()->columnSpanFull()->columns(2)->schema([
                        TextInput::make('purchase_price')
                            ->label('Purchase Price')
                            ->prefixIcon('heroicon-s-currency-dollar')->prefixIconColor('primary')
                            ->numeric()
                            ->nullable(),

                        TextInput::make('size')
                            ->prefixIcon(Heroicon::OutlinedArrowsUpDown)->prefixIconColor('primary')
                            ->label('Size')
                            ->nullable(),
                    ]),
                ]),
            ]);
    }

    /**
     * Step 2: Dates & Warranty - التواريخ والضمان
     */
    public static function getDatesStep(): Step
    {
        return Step::make('Dates')->label('Set Dates & Warranty')
            ->icon('heroicon-o-calendar-date-range')
            ->schema([
                Fieldset::make()->columnSpanFull()->label('Set Dates')->columns(2)->schema([

                    // Warranty & Service Interval
                    Fieldset::make()->columnSpanFull()->columns(3)->schema([
                        TextInput::make('warranty_months')
                            ->label('Warranty (Months)')
                            ->numeric()
                            ->default(12)
                            ->columnSpan(1)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $state = (int) $state;
                                $years = round($state / 12, 2);
                                $set('warranty_years', $years);

                                $purchaseDate = $get('purchase_date');
                                if ($purchaseDate) {
                                    $set('warranty_end_date', Carbon::parse($purchaseDate)->addMonths($state)->format('Y-m-d'));
                                }
                            }),

                        TextInput::make('warranty_years')
                            ->label('Warranty (Years)')
                            ->disabled()
                            ->dehydrated()
                            ->default(1)
                            ->numeric()
                            ->columnSpan(1),

                        TextInput::make('service_interval_days')
                            ->label('Service Interval (Days)')
                            ->numeric()->columnSpan(1)
                            ->default(30)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                $state        = (float) $state;
                                $lastServiced = $get('last_serviced');
                                if ($lastServiced) {
                                    $set('next_service_date', Carbon::parse($lastServiced)->addDays($state)->format('Y-m-d'));
                                }
                            }),
                    ]),

                    // Purchase, Warranty End, Operation Start Dates
                    Grid::make()->columns(3)->columnSpanFull()->schema([
                        DatePicker::make('purchase_date')
                            ->label('Purchase Date')
                            ->prefixIcon('heroicon-s-calendar')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $months = (int) $get('warranty_months');
                                if ($months) {
                                    $set('warranty_end_date', Carbon::parse($state)->addMonths($months)->format('Y-m-d'));
                                }
                            }),

                        DatePicker::make('warranty_end_date')
                            ->label('Warranty End Date')
                            ->prefixIcon('heroicon-s-calendar')->prefixIconColor('primary')
                            ->default(now()),

                        DatePicker::make('operation_start_date')
                            ->label('Operation Start Date')
                            ->prefixIcon('heroicon-s-calendar')->prefixIconColor('primary'),
                    ]),

                    // Last Serviced & Next Service Date
                    DatePicker::make('last_serviced')
                        ->label('Last Serviced')
                        ->prefixIcon('heroicon-s-calendar-date-range')->prefixIconColor('primary')
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, callable $set, $get) {
                            $interval = $get('service_interval_days');
                            if ($interval) {
                                $set('next_service_date', Carbon::parse($state)->addDays((float) $interval)->format('Y-m-d'));
                            }
                        })->hiddenOn('create'),

                    DatePicker::make('next_service_date')
                        ->label('Next Service Date')
                        ->prefixIcon('heroicon-s-calendar')->prefixIconColor('primary')
                        ->default(now())->hiddenOn('create'),
                ]),
            ]);
    }

    /**
     * Step 3: Images - الصور
     */
    public static function getImagesStep(): Step
    {
        return Step::make('Images')
            ->icon('heroicon-o-photo')
            ->schema([
                Fieldset::make()->columnSpanFull()->columns(1)->schema([
                    SpatieMediaLibraryFileUpload::make('images')
                        ->disk('public')
                        ->label('')
                        ->directory('equipments')
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
                            return (string) str($file->getClientOriginalName())->prepend('equipment-');
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
            ]);
    }
}
