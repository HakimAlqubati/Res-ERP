<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources;

use App\Filament\Clusters\HRAttenanceCluster;
use App\Filament\Clusters\HRAttenanceCluster\Resources\LeaveApplicationResource\Pages;
use App\Models\LeaveApplication;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LeaveApplicationResource extends Resource
{
    protected static ?string $model = LeaveApplication::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = HRAttenanceCluster::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('employee_id')
                    ->required()
                    ->label('Employee ID'),

                Select::make('status')
                    ->options([
                        LeaveApplication::getStatus(),
                    ])->disabledOn('create')
                    ->default(LeaveApplication::STATUS_PENDING)
                    ->required()
                    ->label('Status'),
  

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('employee.name')
                    ->sortable()
                    ->searchable(),

                

                TextColumn::make('status')
                // ->formatStateUsing(fn ($state) => LeaveApplication::getStatusLabelAttribute($state))
                    ->sortable()
                    ->searchable(),

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
            'index' => Pages\ListLeaveApplications::route('/'),
            'create' => Pages\CreateLeaveApplication::route('/create'),
            'edit' => Pages\EditLeaveApplication::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
