<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources;

use App\Filament\Clusters\HRAttenanceCluster;
use App\Filament\Clusters\HRAttenanceCluster\Resources\EmployeeOvertimeResource\Pages;
use App\Models\EmployeeOvertime;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EmployeeOvertimeResource extends Resource
{
    protected static ?string $model = EmployeeOvertime::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = HRAttenanceCluster::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 10;
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('employee_id')
                    ->relationship('employee', 'name') // Assuming employee name is displayed
                    ->required(),

                DatePicker::make('date')
                    ->label('Overtime Date')
                    ->required(),

                TimePicker::make('start_time')
                    ->label('Start Time')
                    ->required(),

                TimePicker::make('end_time')
                    ->label('End Time')
                    ->required(),

                TextInput::make('hours')
                    ->label('Total Hours')
                    ->numeric()
                    ->required(),

                TextInput::make('rate')
                    ->label('Rate')
                    ->numeric()
                    ->nullable(),

                TextInput::make('reason')
                    ->label('Reason')
                    ->nullable(),
                TextInput::make('notes')
                    ->label('Notes')
                    ->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.name')
                    ->label('Employee')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('date')
                    ->label('Date')
                    ->sortable()
                    ->date(),

                TextColumn::make('start_time')
                    ->label('Start Time')
                    ->sortable(),

                TextColumn::make('end_time')
                    ->label('End Time')
                    ->sortable(),

                TextColumn::make('hours')
                    ->label('Hours')
                    ->sortable(),

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
            'index' => Pages\ListEmployeeOvertimes::route('/'),
            'create' => Pages\CreateEmployeeOvertime::route('/create'),
            'edit' => Pages\EditEmployeeOvertime::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
