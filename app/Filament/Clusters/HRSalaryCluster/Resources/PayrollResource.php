<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources;

use App\Filament\Clusters\HRSalaryCluster;
use App\Filament\Clusters\HRSalaryCluster\Resources\PayrollResource\Pages;
use App\Filament\Clusters\HRSalaryCluster\Resources\PayrollResource\RelationManagers;
use App\Filament\Clusters\HRSalaryCluster\Resources\PayrollResource\RelationManagers\PayrollsRelationManager;
use App\Filament\Pages\RunPayroll;
use App\Models\Branch;
use App\Models\Payroll;
use App\Models\PayrollRun;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PayrollResource extends Resource
{
    protected static ?string $model = PayrollRun::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = HRSalaryCluster::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        // dd(getMonthOptionsBasedOnSettings());
        // dd(getMonthsArray2());
        return $form
            ->schema([

                Fieldset::make()->label('Set Branch, Month and payment date')->columns(3)->schema([
                    TextInput::make('note_that')->label('Note that!')->columnSpan(3)->hiddenOn('view')
                        ->disabled()
                        // ->extraAttributes(['class' => 'text-red-600'])
                        ->suffixIcon('heroicon-o-exclamation-triangle')
                        ->suffixIconColor('warning')
                        // ->color(Color::Red)
                        ->default('Employees who have not had their work periods added, will not appear on the payroll.'),
                    Select::make('branch_id')->label('Choose branch')
                        ->disabledOn('view')
                        ->options(Branch::where('active', 1)->select('id', 'name')->get()->pluck('name', 'id'))
                        ->required()

                        ->helperText('Please, choose a branch'),
                    Select::make('name')->label('Month')->hiddenOn('view')
                        ->required()
                        ->options(fn() => getMonthOptionsBasedOnSettings()) // Use the helper function

                        // ->searchable()
                        ->default(now()->format('F')),
                    TextInput::make('name')->label('Title')->hiddenOn('create')->disabled(),
                    Forms\Components\DatePicker::make('payment_date')->required()
                        ->default(date('Y-m-d')),
                ]),
                Forms\Components\Textarea::make('notes')->label('Notes')->columnSpanFull(),
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Branch')->sortable(),
                Tables\Columns\TextColumn::make('year')->sortable(),
                Tables\Columns\TextColumn::make('month')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('branch_id')->label('Branch')
                    ->relationship('branch', 'name'),

            ])
            ->actions([
                Tables\Actions\ViewAction::make(), 
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                
                ]),
            ]) 
        ;
    }

    public static function getRelations(): array
    {
        return [ 
            PayrollsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayrolls::route('/'),
            'create' => Pages\CreatePayroll::route('/create'),
            'view' => Pages\ViewPayroll::route('/{record}'),
            'edit' => Pages\EditPayroll::route('/{record}/edit'),
            // 'runPayroll'    => RunPayroll::route('/run-payroll')
        ];
    }
}
