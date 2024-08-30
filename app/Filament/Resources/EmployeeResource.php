<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\HRCluster;
use App\Filament\Resources\EmployeeResource\Pages;
use App\Models\Department;
use App\Models\Employee;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

// use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $cluster = HRCluster::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 1;
    public static function getNavigationLabel(): string
    {
        return __('lang.employees');
    }
    public static function getPluralLabel(): ?string
    {
        return __('lang.employees');
    }

    public static function getLabel(): ?string
    {
        return __('lang.employees');
    }

    public static function form(Form $form): Form
    {

        return $form
            ->schema([

                TextInput::make('name')->required(),
                TextInput::make('email')->email()->required(),
                Fieldset::make('employee_profile')->relationship('employee_profile')->label('Employee profile')
                    ->schema([
                        TextInput::make('employee_no')->label('Employee number'),
                        TextInput::make('job_title'),
                        Select::make('department_id')->label('Department')
                        ->searchable()
                            ->options(Department::select('id', 'name')->get()->pluck('name', 'id')),
                    ]),

                Select::make('roles')
                    ->label('Employee role as user')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->maxItems(1)
                    ->preload()
                    ->searchable(),

                Select::make('owner_id')
                    ->label('Department manager')
                    ->searchable()
                    ->options(function () {
                        return DB::table('users')->pluck('name', 'id');
                    }),

                TextInput::make('password')
                    ->password()
                    ->columnSpanFull()
                // ->required()
                    ->required(fn(string $context) => $context === 'create')
                    ->reactive()
                    ->dehydrateStateUsing(fn($state) => Hash::make($state)),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
        ->defaultSort('id','desc')
            ->columns([
                TextColumn::make('name')
                    ->sortable()->searchable()
                    ->searchable(isIndividual: true, isGlobal: false),
                TextColumn::make('employee_profile.job_title')
                    ->label('Job title')
                    ->sortable()->searchable()
                    ->searchable(isIndividual: true, isGlobal: false),
                TextColumn::make('employee_profile.employee_no')
                    ->label('employee number')
                    ->sortable()->searchable()
                    ->searchable(isIndividual: true, isGlobal: false),
                TextColumn::make('employee_profile.department.name')
                    ->label('Department')
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('active')
                    ->query(fn(Builder $query): Builder => $query->whereNotNull('active')),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                // ExportBulkAction::make(),
                Tables\Actions\RestoreBulkAction::make(),
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
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
        // ->where('role_id',8)
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
    // public function canCreate(){
    //     return false;
    // }
}
