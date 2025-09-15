<?php

namespace App\Filament\Clusters\HRServiceRequestCluster\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Clusters\HRServiceRequestCluster\Resources\EquipmentResource\Pages\ListEquipment;
use App\Filament\Clusters\HRServiceRequestCluster\Resources\EquipmentResource\Pages\CreateEquipment;
use App\Filament\Clusters\HRServiceRequestCluster\Resources\EquipmentResource\Pages\EditEquipment;
use App\Filament\Clusters\HRServiceRequestCluster\Resources\EquipmentResource\Pages\ViewEquipment;
use App\Filament\Clusters\HRServiceRequestCluster;
use App\Filament\Clusters\HRServiceRequestCluster\Resources\EquipmentResource\Pages;
use App\Models\Branch;
use App\Models\BranchArea;
use App\Models\Equipment;
use App\Models\EquipmentType;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Pages\Page;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class EquipmentResource extends Resource
{
    protected static ?string $model = Equipment::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::Wrench;

    protected static ?string $cluster                             = HRServiceRequestCluster::class;
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort                         = 2;
    public static function form(Schema $schema): Schema
    {

        return $schema
            ->components([
                Wizard::make([
                    Step::make('Basic data')
                        ->icon('heroicon-o-bars-3-center-left')
                        ->schema([
                            Fieldset::make()->columnSpanFull()->schema([
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
                                            $set('asset_tag', EquipmentResource::generateEquipmentCode($state));
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

                                Fieldset::make()->columnSpanFull()->label('Set Branch & Area')->columns(2)->schema([

                                    Select::make('branch_id')
                                        ->label('Branch')
                                        ->options(Branch::selectable()
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
                                Grid::make()->columnSpanFull()->columns(3)->schema([
                                    TextInput::make('purchase_price')
                                        ->label('Purchase Price')
                                        ->prefixIcon('heroicon-s-currency-dollar')->prefixIconColor('primary')
                                        ->numeric()
                                        ->nullable(),

                                    TextInput::make('size')
                                        ->prefixIcon('heroicon-s-ellipsis-horizontal-circle')->prefixIconColor('primary')
                                        ->label('Size')
                                        ->nullable(),

                                    TextInput::make('periodic_service')
                                        ->label('Periodic Service (Days)')
                                        ->prefixIcon('heroicon-s-ellipsis-horizontal-circle')->prefixIconColor('primary')
                                        ->numeric()
                                        ->default(0),
                                ]),

                                // Forms\Components\FileUpload::make('warranty_file')
                                //     ->label('Warranty File'),

                                // Forms\Components\FileUpload::make('profile_picture')
                                //     ->label('Profile Picture'),

                            ]),

                        ]),

                    Step::make('Dates')->label('Set Dates & Warranty')
                        ->icon('heroicon-o-calendar-date-range')
                        ->schema([
                            Fieldset::make()->columnSpanFull()->label('Set Dates')->columns(2)->schema([

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
                                        ->dehydrated() // تُرسل القيمة مع الحفظ
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
                                Grid::make()->columns(3)->columnSpanFull()->schema([
                                    DatePicker::make('purchase_date')
                                        ->label('Purchase Date')
                                        ->prefixIcon('heroicon-s-calendar')
                                        // ->default(now())
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
                                        ->prefixIcon('heroicon-s-calendar')->prefixIconColor('primary')
                                    // ->default(now()->subYear())
                                    // ->live(onBlur: true)
                                    // ->afterStateUpdated(function ($state, callable $set, $get) {
                                    //     $months = (int) $get('warranty_months');
                                    //     if ($months) {
                                    //         $set('warranty_end_date', \Carbon\Carbon::parse($state)->addMonths($months)->format('Y-m-d'));
                                    //     }
                                    // })
                                    ,
                                ]),

                                DatePicker::make('last_serviced')
                                    ->label('Last Serviced')
                                    // ->default(now())
                                    ->prefixIcon('heroicon-s-calendar-date-range')->prefixIconColor('primary')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, callable $set, $get) {
                                        $interval = $get('service_interval_days');
                                        if ($interval) {
                                            $set('next_service_date', Carbon::parse($state)->addDays((float) $interval)->format('Y-m-d'));
                                        }
                                    }),

                                DatePicker::make('next_service_date')
                                    ->label('Next Service Date')
                                    ->prefixIcon('heroicon-s-calendar')->prefixIconColor('primary')
                                    ->default(now()),

                            ]),
                        ]),
                    Step::make('Images')
                        ->icon('heroicon-o-photo')
                        ->schema([
                            Fieldset::make()->columnSpanFull()->columns(1)->schema([
                                FileUpload::make('images')
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
                        ]),
                ])->skippable()->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table->striped()->defaultSort('id', 'desc')
            ->columns([
                // SpatieMediaLibraryImageColumn::make('')->label('')->size(50)
                //     ->circular()->alignCenter(true)->getStateUsing(function () {
                //     return null;
                // })->limit(3),
                TextColumn::make('name')->toggleable()
                    ->searchable()
                    ->sortable()->toggleable(isToggledHiddenByDefault: false),
                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => Equipment::STATUS_ACTIVE,
                        'warning' => Equipment::STATUS_UNDER_MAINTENANCE,
                        'danger'  => Equipment::STATUS_RETIRED,
                    ])->alignCenter(true)->toggleable(),
                TextColumn::make('asset_tag')
                    ->searchable()->toggleable()
                    ->sortable()->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('qr_code')
                    ->searchable()->toggleable()->hidden(),
                TextColumn::make('make')->toggleable()
                    ->sortable()->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('model')->toggleable()
                    ->sortable()->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('serial_number')->toggleable()
                    ->searchable()->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('branch.name')->toggleable()
                    ->label('Branch')
                    ->sortable()->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('branchArea.name')->toggleable()
                    ->label('Branch Area')
                    ->sortable()->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('purchase_price')->toggleable()
                    ->money('USD')
                    ->sortable()->toggleable(isToggledHiddenByDefault: true),
                ImageColumn::make('profile_picture')->toggleable()
                    ->label('Profile Picture')
                    ->rounded()->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')->toggleable()
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('operation_start_date')
                    ->label('Operation Start')

                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('warranty_end_date')
                    ->label('Warranty End')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('next_service_date')
                    ->label('Next Service')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->searchable()->options(fn() => Branch::branches()
                        ->active()->pluck('name', 'id')),
                SelectFilter::make('type_id')
                    ->label('Type')
                    ->searchable()->options(fn() => EquipmentType::active()->pluck('name', 'id')),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('qrCodePrint')
                    ->label('Print')
                    ->button()->icon('heroicon-o-qr-code')
                    ->url(fn($record): string => route('testQRCode', ['id' => $record->id]), true),

            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
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
            'index'  => ListEquipment::route('/'),
            'create' => CreateEquipment::route('/create'),
            'edit'   => EditEquipment::route('/{record}/edit'),
            'view'   => ViewEquipment::route('/{record}'),
        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            ListEquipment::class,
            CreateEquipment::class,
            EditEquipment::class,
            ViewEquipment::class,

        ]);
    }
    public static function getNavigationBadge(): ?string
    {
        if (auth()->user()->is_branch_manager) {
            return static::getModel()::where('branch_id', auth()->user()->branch->id)->count();
        }
        return static::getModel()::count();
    }

    public static function generateEquipmentCode(?int $typeId): string
    {
        return DB::transaction(function () use ($typeId) {
            // جلب نوع الجهاز مع علاقته بالفئة
            $equipmentType = EquipmentType::with('category')->find($typeId);

            // استخراج البوادئ من الفئة والنوع، أو تعيين قيم افتراضية
            $categoryPrefix = $equipmentType?->category?->equipment_code_start_with ?? 'EQ-';
            $typeCode       = $equipmentType?->code ?? 'GEN';

            // دمج البادئة النهائية: CategoryPrefix + TypeCode
            $prefix = $categoryPrefix . '-' . $typeCode;

            // قفل السجلات المماثلة لمنع التكرار
            $lastAssetTag = Equipment::where('asset_tag', 'like', $prefix . '%')
                ->lockForUpdate()
                ->orderByDesc('asset_tag')
                ->value('asset_tag');

            // استخراج الرقم الأخير إن وُجد
            $lastNumber = 0;
            if ($lastAssetTag && preg_match('/(\d+)$/', $lastAssetTag, $matches)) {
                $lastNumber = (int) $matches[1];
            }

            // توليد الرقم الجديد
            $nextNumber = $lastNumber + 1;

            // إعادة الكود الكامل بالشكل: CATEGORYPREFIX + TYPECODE + 3 أرقام
            return $prefix . '-' . str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT);
        });
    }
}
