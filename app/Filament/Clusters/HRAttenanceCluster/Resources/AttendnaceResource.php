<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources;

use App\Filament\Clusters\HRAttenanceCluster;
use App\Filament\Clusters\HRAttenanceCluster\Resources\AttendnaceResource\Pages;
use App\Filament\Clusters\HRAttenanceCluster\Resources\AttendnaceResource\RelationManagers;
use App\Models\Attendance;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AttendnaceResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = HRAttenanceCluster::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 2;
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make()->label('Select employee and check type')->schema([
                    Forms\Components\Select::make('employee_id')
                        ->label('Employee')
                        ->default(auth()->user()?->employee?->id)
                        ->disabled()
                        ->relationship('employee', 'name')
                        ->required(),
                    Forms\Components\ToggleButtons::make('check_type')
                        ->label('Check type')
                        ->inline()
                        ->default(Attendance::CHECKTYPE_CHECKIN)
                        ->options(Attendance::getCheckTypes())
                        ->required(),

                ]),

                Fieldset::make()->label('Select date & time')->schema([
                    Grid::make()->columns(3)->schema([

                        Forms\Components\DatePicker::make('check_date')
                            ->label('Check date')
                            ->required()
                            ->default(date('Y-m-d'))
                            ->live()
                            ->afterStateUpdated(function (?string $state, $component, $set) {
                                $set('day', Carbon::parse($state)->format('l'));
                            }),

                        Forms\Components\TimePicker::make('check_time')
                            ->label('Check time')
                            ->default(now())
                            ->required(),
                        TextInput::make('day')->label('Day')->disabled()->default(Carbon::parse(date('Y-m-d'))->format('l')),
                    ])
                ]),


                Forms\Components\Textarea::make('notes')
                    ->label('Notes')
                    ->columnSpanFull()
                    ->nullable(),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.name')
                    ->label('Employee')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('check_type')
                    ->label('Check Type')
                    ->sortable(),

                Tables\Columns\TextColumn::make('check_date')
                    ->label('Check Date')
                    ->sortable(),

                Tables\Columns\TextColumn::make('check_time')
                    ->label('Check Time'),
                Tables\Columns\TextColumn::make('day')
                    ->label('Day'),
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
            'index' => Pages\ListAttendnaces::route('/'),
            'create' => Pages\CreateAttendnace::route('/create'),
            'edit' => Pages\EditAttendnace::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('employee_id',auth()->user()?->employee?->id)->count();
    }

    public static function getEloquentQuery(): Builder
    {
        $query = static::getModel()::query()->where('employee_id',auth()->user()?->employee?->id);

        if (
            static::isScopedToTenant() &&
            ($tenant = Filament::getTenant())
        ) {
            static::scopeEloquentQueryToTenant($query, $tenant);
        }

        return $query;
    }
}
