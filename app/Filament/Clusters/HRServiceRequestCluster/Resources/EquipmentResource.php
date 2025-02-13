<?php

namespace App\Filament\Clusters\HRServiceRequestCluster\Resources;

use App\Filament\Clusters\HRServiceRequestCluster;
use App\Filament\Clusters\HRServiceRequestCluster\Resources\EquipmentResource\Pages;
use App\Filament\Clusters\HRServiceRequestCluster\Resources\EquipmentResource\RelationManagers;
use App\Models\Equipment;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

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
                Fieldset::make()->schema([
                    Grid::make()->columns(3)->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Name')
                            ->required()->prefixIconColor('primary')->columnSpan(2)
                            ->unique(ignoreRecord: true)->prefixIcon('heroicon-s-information-circle'),
                        Forms\Components\TextInput::make('asset_tag')
                            ->label('Asset Tag')
                            ->required()->prefixIconColor('primary')
                            ->unique(ignoreRecord: true)->prefixIcon('heroicon-s-tag'),

                        Forms\Components\TextInput::make('qr_code')->prefixIcon('heroicon-s-qr-code')->prefixIconColor('primary')
                            ->label('QR Code')
                            ->required()
                            ->unique(ignoreRecord: true)->hidden(),
                    ]),

                    Forms\Components\TextInput::make('serial_number')
                        ->label('Serial Number')->prefixIcon('heroicon-s-ellipsis-vertical')->prefixIconColor('primary')
                        ->required()
                        ->unique(ignoreRecord: true),

                    Forms\Components\Select::make('branch_id')
                        ->label('Branch')
                        ->relationship('branch', 'name')
                        ->required(),

                    Forms\Components\TextInput::make('make')
                        ->label('Make')
                        ->nullable()
                        ->prefixIcon('heroicon-s-bookmark-square')->prefixIconColor('primary'),

                    Forms\Components\TextInput::make('model')
                        ->label('Model')
                        ->prefixIcon('heroicon-s-wallet')->prefixIconColor('primary')
                        ->nullable(),

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

                    Forms\Components\DatePicker::make('last_serviced')
                        ->label('Last Serviced')
                        ->prefixIcon('heroicon-s-calendar-date-range')->prefixIconColor('primary')
                        ->nullable(),

                    Forms\Components\FileUpload::make('warranty_file')
                        ->label('Warranty File')
                        ->nullable(),

                    Forms\Components\FileUpload::make('profile_picture')
                        ->label('Profile Picture')
                        ->nullable(),
                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table->striped()
            ->columns([
                TextColumn::make('name')->toggleable()
                    ->searchable()
                    ->sortable()->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('asset_tag')
                    ->searchable()->toggleable()
                    ->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('qr_code')
                    ->searchable()->toggleable()->hidden(),
                TextColumn::make('make')->toggleable()
                    ->sortable()->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('model')->toggleable()
                    ->sortable()->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('serial_number')->toggleable()
                    ->searchable()->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('purchase_price')->toggleable()
                    ->money('USD')
                    ->sortable()->toggleable(isToggledHiddenByDefault: true),
                ImageColumn::make('profile_picture')->toggleable()
                    ->label('Profile Picture')
                    ->rounded()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('branch.name')->toggleable()
                    ->label('Branch')
                    ->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->toggleable()
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->searchable()->options(fn() => \App\Models\Branch::active()->pluck('name', 'id')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('qrCodePrint')
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
}
