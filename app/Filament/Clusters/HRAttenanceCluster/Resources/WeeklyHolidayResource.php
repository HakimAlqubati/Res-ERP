<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources;

use App\Filament\Clusters\HRAttenanceCluster;
use App\Filament\Clusters\HRAttenanceCluster\Resources\WeeklyHolidayResource\Pages;
use App\Filament\Clusters\HRAttenanceCluster\Resources\WeeklyHolidayResource\RelationManagers;
use App\Filament\Clusters\HRLeaveManagementCluster;
use App\Models\WeeklyHoliday;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WeeklyHolidayResource extends Resource
{
    protected static ?string $model = WeeklyHoliday::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = HRLeaveManagementCluster::class;
    protected static ?string $label = 'Weekend';
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 3;
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make()->schema([
                    Forms\Components\TextInput::make('description')
                        ->label('Description')
                        ->nullable(),

                    Forms\Components\Select::make('days')
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
                Tables\Columns\TextColumn::make('description')->label('Description'),
                Tables\Columns\TextColumn::make('days')
                    ->label('Days')
                    ->formatStateUsing(fn($state) => implode(', ', explode(',', $state))), // Display as comma-separated list
                Tables\Columns\TextColumn::make('created_at')->label('Created At')->dateTime(),

            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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

    public static function getEloquentQuery(): Builder
    {
        // Limit the resource to only one row
        return parent::getEloquentQuery()->limit(1);
    }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWeeklyHolidays::route('/'),
            'create' => Pages\CreateWeeklyHoliday::route('/create'),
            'edit' => Pages\EditWeeklyHoliday::route('/{record}/edit'),
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
