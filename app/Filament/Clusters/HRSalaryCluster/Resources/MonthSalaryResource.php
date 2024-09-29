<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources;

use App\Filament\Clusters\HRSalaryCluster;
use App\Filament\Clusters\HRSalaryCluster\Resources\MonthSalaryResource\Pages;
use App\Filament\Clusters\HRSalaryCluster\Resources\MonthSalaryResource\RelationManagers;
use App\Models\MonthSalary;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MonthSalaryResource extends Resource
{
    protected static ?string $model = MonthSalary::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = HRSalaryCluster::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 1;
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')->required(),
                Forms\Components\DatePicker::make('month')->required(),
                Forms\Components\DatePicker::make('start_month')->required(),
                Forms\Components\DatePicker::make('end_month')->required(),
                Forms\Components\Textarea::make('notes'),
                Forms\Components\DatePicker::make('payment_date')->required(),
                Forms\Components\Toggle::make('approved'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('month')->date(),
                Tables\Columns\TextColumn::make('start_month')->date(),
                Tables\Columns\TextColumn::make('end_month')->date(),
                Tables\Columns\TextColumn::make('payment_date')->date(),
                Tables\Columns\ToggleColumn::make('approved'),
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
            'index' => Pages\ListMonthSalaries::route('/'),
            'create' => Pages\CreateMonthSalary::route('/create'),
            'edit' => Pages\EditMonthSalary::route('/{record}/edit'),
        ];
    }
}
