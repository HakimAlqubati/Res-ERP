<?php

namespace App\Filament\Clusters\HRCluster\Resources;

use App\Filament\Clusters\HRCluster;
use App\Filament\Clusters\HRCluster\Resources\DepartmentResource\Pages;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Administration;
use App\Models\Branch;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DepartmentResource extends Resource
{
    protected static ?string $model = Department::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = HRCluster::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 4;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make('')->columns(3)->label('')->schema([
                    TextInput::make('name')->required(),

                    Toggle::make('active')->default(1)->inline(false),

                    Toggle::make('is_global')
                        ->live()
                        ->helperText('If this is a global administration, it will be available to all branches')
                        ->default(1)->inline(false),

                    Grid::make()->columnSpanFull()->columns(4)->schema([
                        Select::make('branch_id')->searchable()
                            ->label('Branch')->live()
                            ->options(Branch::where('active', 1)
                                ->select('id', 'name')->get()->pluck('name', 'id'))
                            ->helperText('Choose branch')->live()
                            ->visible(fn($get) => $get('is_global') == 0),
                        Select::make('manager_id')->label('Manager')
                            ->searchable()
                            ->options(function ($get) {
                                if ($get('is_global') == 1) {
                                    return Employee::employeeTypesManagers()->active()->whereDoesntHave('managedDepartment')
                                        ->select('id', 'name')->get()->pluck('name', 'id');
                                } else {
                                    return Employee::forBranch($get('branch_id'))
                                        ->employeeTypesManagers()
                                        ->whereDoesntHave('managedDepartment')
                                        ->active()
                                        ->select('id', 'name')->get()->pluck('name', 'id');
                                }
                            }),
                        Select::make('administration_id')->label('Administration')
                            ->required()
                            ->searchable()->options(function ($get) {
                                if ($get('is_global') == 1) {
                                    return Administration::select('id', 'name')->get()->pluck('name', 'id');
                                } else {
                                    return Administration::forBranch($get('branch_id'))
                                        ->select('id', 'name')->get()->pluck('name', 'id');
                                }
                            }),
                        Select::make('parent_id')->label('Parent')
                            ->searchable()->options(function ($get) {
                                if ($get('is_global') == 1) {
                                    return Department::global()
                                        ->select('id', 'name')->get()->pluck('name', 'id');
                                } else {
                                    return Department::forBranch($get('branch_id'))
                                        ->select('id', 'name')->get()->pluck('name', 'id');
                                }
                            }),
                    ]),
                    Textarea::make('description')->columnSpanFull(),
                ]),

            ]);
    }

    public static function table(Table $table): Table
    {
        // $dept  = Department::find(5);
        // $ancestors = $dept->ancestors();
        // foreach ($ancestors as $d) {
        //     $arr[] = ($d->manager->name);
        // }
        return $table
            ->columns([
                TextColumn::make('id')->searchable()->sortable(),
                TextColumn::make('name')->searchable(),
                TextColumn::make('manager.name')->searchable(),
                TextColumn::make('administration.name')->searchable(),
                TextColumn::make('parent.name')
                    ->label('Parent department')
                    ->searchable(),

                ToggleColumn::make('active'),
            ])
            ->filters([])
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
            'index' => Pages\ListDepartments::route('/'),
            // 'create' => Pages\CreateDepartment::route('/create'),
            // 'edit' => Pages\EditDepartment::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        if (isSuperAdmin() || isSystemManager() || isBranchManager()) {
            return true;
        }
        return false;
    }
}
