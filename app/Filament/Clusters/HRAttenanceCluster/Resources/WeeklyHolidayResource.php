<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Fieldset;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Clusters\HRAttenanceCluster\Resources\WeeklyHolidayResource\Pages\ListWeeklyHolidays;
use App\Filament\Clusters\HRAttenanceCluster\Resources\WeeklyHolidayResource\Pages\CreateWeeklyHoliday;
use App\Filament\Clusters\HRAttenanceCluster\Resources\WeeklyHolidayResource\Pages\EditWeeklyHoliday;
use App\Filament\Clusters\HRAttenanceCluster;
use App\Filament\Clusters\HRAttenanceCluster\Resources\WeeklyHolidayResource\Pages;
use App\Filament\Clusters\HRAttenanceCluster\Resources\WeeklyHolidayResource\RelationManagers;
use App\Filament\Clusters\HRLeaveManagementCluster;
use App\Models\WeeklyHoliday;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WeeklyHolidayResource extends Resource
{
    protected static ?string $model = WeeklyHoliday::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = HRLeaveManagementCluster::class;
    protected static ?string $label = 'Weekend';
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 3;
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Fieldset::make()->schema([
                    TextInput::make('description')
                        ->label('Description')
                        ->nullable(),

                    Select::make('days')
                        ->label('Weekly Holiday Days')
                        ->multiple()
                        ->options([
                            'Monday' => 'Monday',
                            'Tuesday' => 'Tuesday',
                            'Wednesday' => 'Wednesday',
                            'Thursday' => 'Thursday',
                            'Friday' => 'Friday',
                            'Saturday' => 'Saturday',
                            'Sunday' => 'Sunday',
                        ])
                        ->required(),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('description')->label('Description'),
                TextColumn::make('days')
                    ->label('Days')
                    ->formatStateUsing(fn($state) => implode(', ', explode(',', $state))), // Display as comma-separated list
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

    public static function getEloquentQuery(): Builder
    {
        // Limit the resource to only one row
        return parent::getEloquentQuery()->limit(1);
    }
    public static function getPages(): array
    {
        return [
            'index' => ListWeeklyHolidays::route('/'),
            'create' => CreateWeeklyHoliday::route('/create'),
            'edit' => EditWeeklyHoliday::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function canCreate(): bool
    {
        if (WeeklyHoliday::count() > 0) {
            return false;
        }
        return static::can('create');
    }

    public static function canViewAny(): bool
    {
        if(isSystemManager() || isSuperAdmin()){
            return true;
        }
        return false;
    }
}
