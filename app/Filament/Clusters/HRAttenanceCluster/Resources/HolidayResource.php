<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources;

use App\Filament\Clusters\HRAttenanceCluster;
use App\Filament\Clusters\HRAttenanceCluster\Resources\HolidayResource\Pages;
use App\Filament\Clusters\HRAttenanceCluster\Resources\HolidayResource\RelationManagers;
use App\Models\Holiday;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class HolidayResource extends Resource
{
    protected static ?string $model = Holiday::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = HRAttenanceCluster::class;
    protected static ?string $label = 'Public Holidays';
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Holiday Name')
                    ->unique(Holiday::class, 'name', ignoreRecord: true)
                    ->required(),

                Forms\Components\DatePicker::make('from_date')
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

                Forms\Components\DatePicker::make('to_date')
                    ->label('To Date')
                    ->default(\Carbon\Carbon::tomorrow()->addDays(1)->format('Y-m-d'))
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

                Forms\Components\TextInput::make('count_days')->disabled()
                    ->label('Number of Days')
                    ->helperText('Type how many days this holiday will be ?')
                    ->numeric()
                    ->default(2)
                    ->required(),


                Forms\Components\Toggle::make('active')
                    ->label('Active')
                    ->default(true),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Holiday'),
                Tables\Columns\TextColumn::make('from_date')->label('From Date')->date(),
                Tables\Columns\TextColumn::make('to_date')->label('To Date')->date(),
                Tables\Columns\TextColumn::make('count_days')->label('Number of Days'),
                Tables\Columns\BooleanColumn::make('active')
                    ->label('Active')
                    ->boolean(),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHolidays::route('/'),
            'create' => Pages\CreateHoliday::route('/create'),
            'edit' => Pages\EditHoliday::route('/{record}/edit'),
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
