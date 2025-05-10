<?php

namespace App\Filament\Clusters\HRServiceRequestCluster\Resources;

use App\Filament\Clusters\HRServiceRequestCluster;
use App\Filament\Clusters\HRServiceRequestCluster\Resources\EquipmentResource\Pages;
use App\Filament\Clusters\HRServiceRequestCluster\Resources\EquipmentResource\RelationManagers;
use App\Models\Branch;
use App\Models\BranchArea;
use App\Models\Equipment;
use App\Models\EquipmentType;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class EquipmentResource extends Resource
{
    protected static ?string $model = Equipment::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = HRServiceRequestCluster::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 2;
    public static function form(Form $form): Form
    {

        return $form
            ->schema([
                Wizard::make([
                    Wizard\Step::make('Basic data')
                        ->icon('heroicon-o-bars-3-center-left')
                        ->schema([
                            Fieldset::make()->schema([
                                Grid::make()->columns(2)->schema([
                                    Forms\Components\TextInput::make('name')
                                        ->label('Name')
                                        ->required()->prefixIconColor('primary')->columnSpan(1)
                                        ->unique(ignoreRecord: true)->prefixIcon('heroicon-s-information-circle'),
                                    Forms\Components\Select::make('status')
                                        ->label('Status')->required()
                                        ->options(Equipment::STATUS_LABELS)->default(Equipment::STATUS_ACTIVE)
                                        ->prefixIcon('heroicon-s-chart-bar-square')->prefixIconColor('primary'),

                                    Forms\Components\Select::make('type_id')
                                        ->label('Type')
                                        ->options(EquipmentType::active()->pluck('name', 'id'))
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, callable $set) {
                                            $set('asset_tag', EquipmentResource::generateEquipmentCode($state));
                                        })
                                        ->prefixIcon('heroicon-s-ellipsis-horizontal')->prefixIconColor('primary'),

                                    Forms\Components\TextInput::make('asset_tag')
                                        ->label('Asset Tag')->prefixIconColor('primary')
                                        ->required()->prefixIconColor('primary')->readOnly()
                                        ->unique(ignoreRecord: true)->prefixIcon('heroicon-s-tag'),
                                    Forms\Components\TextInput::make('qr_code')->prefixIcon('heroicon-s-qr-code')->prefixIconColor('primary')
                                        ->label('QR Code')
                                        ->required()
                                        ->unique(ignoreRecord: true)->hidden(),
                                ]),


                                Fieldset::make()->label('Set Branch & Area')->columns(2)->schema([

                                    Forms\Components\Select::make('branch_id')
                                        ->label('Branch')
                                        ->options(Branch::branches()->active()->pluck('name', 'id'))
                                        ->required()->live()->prefixIcon('heroicon-s-ellipsis-horizontal')->prefixIconColor('primary'),
                                    Select::make('branch_area_id')->label('Branch area')
                                        ->options(function ($get) {
                                            return BranchArea::query()
                                                ->where('branch_id', $get('branch_id'))
                                                ->pluck('name', 'id');
                                        })->prefixIcon('heroicon-s-ellipsis-horizontal')->prefixIconColor('primary'),
                                ]),

                                Grid::make()->columns(3)->schema([
                                    Forms\Components\TextInput::make('serial_number')
                                        ->label('Serial Number')->prefixIcon('heroicon-s-ellipsis-vertical')->prefixIconColor('primary')

                                        ->unique(ignoreRecord: true),
                                    Forms\Components\TextInput::make('make')
                                        ->label('Make')
                                        ->nullable()
                                        ->prefixIcon('heroicon-s-bookmark-square')->prefixIconColor('primary'),

                                    Forms\Components\TextInput::make('model')
                                        ->label('Model')
                                        ->prefixIcon('heroicon-s-wallet')->prefixIconColor('primary')
                                        ->nullable(),

                                ]),
                                Grid::make()->columns(3)->schema([
                                    Forms\Components\TextInput::make('purchase_price')
                                        ->label('Purchase Price')
                                        ->prefixIcon('heroicon-s-currency-dollar')->prefixIconColor('primary')
                                        ->numeric()
                                        ->nullable(),

                                    Forms\Components\TextInput::make('size')
                                        ->prefixIcon('heroicon-s-ellipsis-horizontal-circle')->prefixIconColor('primary')
                                        ->label('Size')
                                        ->nullable(),

                                    Forms\Components\TextInput::make('periodic_service')
                                        ->label('Periodic Service (Days)')
                                        ->prefixIcon('heroicon-s-ellipsis-horizontal-circle')->prefixIconColor('primary')
                                        ->numeric()
                                        ->default(0),
                                ]),

                                // Forms\Components\FileUpload::make('warranty_file')
                                //     ->label('Warranty File'),

                                // Forms\Components\FileUpload::make('profile_picture')
                                //     ->label('Profile Picture'),

                            ])

                        ]),
                    Wizard\Step::make('Dates')->label('Set Dates & Warranty')
                        ->icon('heroicon-o-calendar-date-range')
                        ->schema([

                            Fieldset::make()->label('Set Dates')->columns(2)->schema([

                                Forms\Components\TextInput::make('warranty_years')
                                    ->label('Warranty (Years)')
                                    ->numeric()->columnSpan(1)
                                    ->default(1)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $purchaseDate = $get('purchase_date');
                                        if ($purchaseDate) {
                                            $set('warranty_end_date', \Carbon\Carbon::parse($purchaseDate)->addYears((float)$state)->format('Y-m-d'));
                                        }
                                    }),

                                Forms\Components\TextInput::make('service_interval_days')
                                    ->label('Service Interval (Days)')
                                    ->numeric()->columnSpan(1)
                                    ->default(30)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, callable $set,   $get) {
                                        $state = (float)$state;
                                        $lastServiced = $get('last_serviced');
                                        if ($lastServiced) {
                                            $set('next_service_date', \Carbon\Carbon::parse($lastServiced)->addDays($state)->format('Y-m-d'));
                                        }
                                    }),
                                Grid::make()->columns(3)->columnSpanFull()->schema([
                                    Forms\Components\DatePicker::make('purchase_date')
                                        ->label('Purchase Date')
                                        ->prefixIcon('heroicon-s-calendar')
                                        ->default(now())
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                            $years = (float) $get('warranty_years');
                                            if ($years) {
                                                $set('warranty_end_date', \Carbon\Carbon::parse($state)->addYears($years)->format('Y-m-d'));
                                            }
                                        }),

                                    Forms\Components\DatePicker::make('warranty_end_date')
                                        ->label('Warranty End Date')
                                        ->prefixIcon('heroicon-s-calendar')->prefixIconColor('primary')
                                        ->default(now()),

                                    Forms\Components\DatePicker::make('operation_start_date')
                                        ->label('Operation Start Date')
                                        ->prefixIcon('heroicon-s-calendar')->prefixIconColor('primary')
                                        ->default(now()->subYear())
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function ($state, callable $set,   $get) {
                                            $years = $get('warranty_years');
                                            if ($years) {
                                                $set('warranty_end_date', \Carbon\Carbon::parse($state)->addYears($years)->format('Y-m-d'));
                                            }
                                        }),

                                ]),
                                Forms\Components\DatePicker::make('last_serviced')
                                    ->label('Last Serviced')->default(now())
                                    ->prefixIcon('heroicon-s-calendar-date-range')->prefixIconColor('primary')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, callable $set,   $get) {
                                        $interval = $get('service_interval_days');
                                        if ($interval) {
                                            $set('next_service_date', \Carbon\Carbon::parse((float)$state)->addDays($interval)->format('Y-m-d'));
                                        }
                                    }),




                                Forms\Components\DatePicker::make('next_service_date')
                                    ->label('Next Service Date')
                                    ->prefixIcon('heroicon-s-calendar')->prefixIconColor('primary')
                                    ->default(now()),
                            ])

                        ]),
                    Wizard\Step::make('Images')
                        ->icon('heroicon-o-photo')
                        ->schema([
                            Fieldset::make()->columns(1)->schema([
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
                                    ->circleCropper()
                            ])
                        ]),
                ])->skippable()->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table->striped()->defaultSort('id', 'desc')
            ->columns([
                SpatieMediaLibraryImageColumn::make('')->label('')->size(50)
                    ->circular()->alignCenter(true)->getStateUsing(function () {
                        return null;
                    })->limit(3),
                TextColumn::make('name')->toggleable()
                    ->searchable()
                    ->sortable()->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\BadgeColumn::make('status')
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
                    ->searchable()->options(fn() => \App\Models\Branch::branches()
                        ->active()->pluck('name', 'id')),
                SelectFilter::make('type_id')
                    ->label('Type')
                    ->searchable()->options(fn() => \App\Models\EquipmentType::active()->pluck('name', 'id')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('qrCodePrint')
                    ->label('Print')
                    ->button()->icon('heroicon-o-qr-code')
                    ->url(fn($record): string => route('testQRCode', ['id' => $record->id]), true),

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListEquipment::route('/'),
            'create' => Pages\CreateEquipment::route('/create'),
            'edit' => Pages\EditEquipment::route('/{record}/edit'),
            'view' => Pages\ViewEquipment::route('/{record}'),
        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\ListEquipment::class,
            Pages\CreateEquipment::class,
            Pages\EditEquipment::class,
            Pages\ViewEquipment::class,

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
        $prefix = \App\Models\EquipmentType::find($typeId)?->equipment_code_start_with ?? 'EQ';

        // يمكنك استخدام رقم تسلسلي مختلف إذا أردت أن يكون خاص لكل نوع
        $lastId = \App\Models\Equipment::max('id') + 1;

        return $prefix . str_pad((string)$lastId, 4, '0', STR_PAD_LEFT);
    }
}
