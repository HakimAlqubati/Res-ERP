<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Carbon\Carbon;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Clusters\HRAttenanceCluster\Resources\HolidayResource\Pages\ListHolidays;
use App\Filament\Clusters\HRAttenanceCluster\Resources\HolidayResource\Pages\CreateHoliday;
use App\Filament\Clusters\HRAttenanceCluster\Resources\HolidayResource\Pages\EditHoliday;
use App\Filament\Clusters\HRAttenanceCluster;
use App\Filament\Clusters\HRAttenanceCluster\Resources\HolidayResource\Pages;
use App\Filament\Clusters\HRAttenanceCluster\Resources\HolidayResource\RelationManagers;
use App\Filament\Clusters\HRLeaveManagementCluster;
use App\Models\Holiday;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class HolidayResource extends Resource
{
    protected static ?string $model = Holiday::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = HRLeaveManagementCluster::class;
    protected static ?string $label = 'Public Holidays';
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Holiday Name')
                    ->unique(Holiday::class, 'name', ignoreRecord: true)
                    ->required(),

                DatePicker::make('from_date')
                    ->label('From Date')
                    ->reactive()
                    ->default(date('Y-m-d'))
                    ->required()
                    ->afterStateUpdated(function ($state, callable $set, $get) {
                        $fromDate = $get('from_date');
                        $toDate = $get('to_date');

                        if ($fromDate && $toDate) {
                            $daysDiff = now()->parse($fromDate)->diffInDays(now()->parse($toDate)) + 1;
                            $set('count_days', $daysDiff); // Set the count_days automatically
                        } else {
                            $set('count_days', 0); // Reset if no valid dates are selected
                        }
                    }),

                DatePicker::make('to_date')
                    ->label('To Date')
                    ->default(Carbon::tomorrow()->addDays(1)->format('Y-m-d'))
                    ->reactive()
                    ->required()
                    ->afterStateUpdated(function ($state, callable $set, $get) {
                        $fromDate = $get('from_date');
                        $toDate = $get('to_date');

                        if ($fromDate && $toDate) {
                            $daysDiff = now()->parse($fromDate)->diffInDays(now()->parse($toDate)) + 1;
                            $set('count_days', $daysDiff); // Set the count_days automatically
                        } else {
                            $set('count_days', 0); // Reset if no valid dates are selected
                        }
                    }),

                TextInput::make('count_days')->disabled()
                    ->label('Number of Days')
                    ->helperText('Type how many days this holiday will be ?')
                    ->numeric()
                    ->default(2)
                    ->required(),


                Toggle::make('active')
                    ->label('Active')
                    ->default(true),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Holiday'),
                TextColumn::make('from_date')->label('From Date')->date(),
                TextColumn::make('to_date')->label('To Date')->date(),
                TextColumn::make('count_days')->label('Number of Days'),
                BooleanColumn::make('active')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('created_at')->label('Created At')->dateTime(),

            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
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
            'index' => ListHolidays::route('/'),
            'create' => CreateHoliday::route('/create'),
            'edit' => EditHoliday::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function canViewAny(): bool
    {
        if(isSystemManager() || isSuperAdmin()){
            return true;
        }
        return false;
    }
}
