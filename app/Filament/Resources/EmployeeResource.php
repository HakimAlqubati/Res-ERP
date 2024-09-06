<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\HRCluster;
use App\Filament\Resources\EmployeeResource\Pages;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeFileType;
use App\Models\Position;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Wizard;
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


                Wizard::make([
                    Wizard\Step::make('Personal & Employeement data')
                        ->schema([
                            Fieldset::make('personal_data')->label('Personal data')
                                ->schema([
                                    Grid::make()->columns(3)->schema([
                                        TextInput::make('name')->columnSpan(1)->required(),
                                        TextInput::make('email')->columnSpan(1)->email()->unique(ignoreRecord: true),
                                        TextInput::make('phone_number')->unique(ignoreRecord: true)->columnSpan(1)->numeric()->maxLength(12)->minLength(8),
                                    ]),
                                ]),
                            Fieldset::make('Employeement')->label('Employeement')
                                ->schema([
                                    Grid::make()->columns(4)->schema([
                                        TextInput::make('employee_no')->columnSpan(1)->label('Employee number')->unique(ignoreRecord: true),
                                        TextInput::make('job_title')->columnSpan(1)->required(),
                                        Select::make('position_id')->columnSpan(1)->label('Position')
                                            ->searchable()
                                            ->options(Position::where('active', 1)->select('id', 'title')->get()->pluck('title', 'id'))->required(),
                                        Select::make('department_id')->columnSpan(1)->label('Department')
                                            ->searchable()
                                            ->options(Department::where('active', 1)->select('id', 'name')->get()->pluck('name', 'id'))->required(),
                                        Select::make('branch_id')->columnSpan(1)->label('Branch')
                                            ->searchable()
                                            ->options(Branch::where('active', 1)->select('id', 'name')->get()->pluck('name', 'id')),
                                    ]),
                                ]),
                        ]),
                    Wizard\Step::make('Employee files')
                        ->schema([
                            Repeater::make('files')
                                ->relationship()
                                ->columns(2)
                                ->schema([

                                    Fieldset::make()->schema([
                                        Grid::make()->columns(2)->schema([
                                            Select::make('file_type_id')
                                                ->label('File type')
                                                ->options(EmployeeFileType::select('id', 'name')->where('active', 1)->get()->pluck('name', 'id'))
                                                ->searchable(),
                                            FileUpload::make('attachment')->label('Attach your file')->downloadable()->previewable(),
                                        ]),
                                    ])
                                ])
                        ]),
                ])->columnSpanFull(),



            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->sortable()->searchable()
                    ->searchable(isIndividual: true, isGlobal: false),
                TextColumn::make('position.title')
                    ->label('Position')
                    ->searchable(),
                TextColumn::make('job_title')
                    ->label('Job title')
                    ->sortable()->searchable()
                    ->searchable(isIndividual: true, isGlobal: false),
                TextColumn::make('employee_no')
                    ->label('employee number')
                    ->sortable()->searchable()
                    ->searchable(isIndividual: true, isGlobal: false),
                TextColumn::make('department.name')
                    ->label('Department')
                    ->searchable(),
                TextColumn::make('unrequired_documents_count')->label('Unrequired docs')->alignCenter(true)
                    ->formatStateUsing(function ($state) {

                        return '(' . $state . ') docs of ' . EmployeeFileType::getCountByRequirement()['unrequired_count'];
                    }),
                TextColumn::make('required_documents_count')->label('Required docs')->alignCenter(true)
                    ->formatStateUsing(function ($state) {

                        return '(' . $state . ') docs of ' . EmployeeFileType::getCountByRequirement()['required_count'];
                    })

                // ->default(function ($record) {
                //     return $record->required_documents_count . ' of ' . 10;
                // })
                ,
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
