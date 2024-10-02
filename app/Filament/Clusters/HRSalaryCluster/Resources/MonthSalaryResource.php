<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources;

use App\Filament\Clusters\HRSalaryCluster;
use App\Filament\Clusters\HRSalaryCluster\Resources\MonthSalaryResource\Pages;
use App\Filament\Clusters\HRSalaryCluster\Resources\MonthSalaryResource\RelationManagers\DetailsRelationManager;
use App\Models\Branch;
use App\Models\MonthSalary;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

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

                Fieldset::make()->label('Set Branch, Month and payment date')->columns(3)->schema([
                    TextInput::make('note_that')->label('Note that!')->columnSpan(3)->hiddenOn('edit')
                        ->disabled()
                    // ->extraAttributes(['class' => 'text-red-600'])
                        ->suffixIcon('heroicon-o-exclamation-triangle')
                        ->suffixIconColor('warning')
                    // ->color(Color::Red)
                        ->default('Employees who have not had their work periods added, will not appear on the payroll.'),
                    Select::make('branch_id')->label('Choose branch')
                    ->disabledOn('edit')
                        ->options(Branch::where('active', 1)->select('id', 'name')->get()->pluck('name', 'id'))
                        ->required()
                        ->helperText('Please, choose a branch'),
                    Select::make('name')->label('Month')->hiddenOn('edit')
                        ->required()
                        ->options(function () {
                            // Get the array of months
                            $months = getMonthsArray();

                            // Map the months to a key-value pair with month names
                            return collect($months)->mapWithKeys(function ($month, $key) {
                                return [$key => $month['name']]; // Using month key as the option key
                            });
                        })
                        ->searchable()
                        ->default(now()->format('F'))
                    ,
                    TextInput::make('name')->label('Title')->hiddenOn('create')->disabled(),
                    Forms\Components\DatePicker::make('payment_date')->required()
                        ->default(date('Y-m-d'))
                    ,
                ]),
                Forms\Components\Textarea::make('notes')->label('Notes')->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Title')->searchable(),
                Tables\Columns\TextColumn::make('notes'),
                Tables\Columns\TextColumn::make('branch.name')->label('Branch')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('createdBy.name')->label('Created by')->searchable()->sortable(),
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
            DetailsRelationManager::class,
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

    public static function canDelete(Model $record): bool
    {
        return false;
    }


    public static function canDeleteAny(): bool
    {
        return false;
    }
}
