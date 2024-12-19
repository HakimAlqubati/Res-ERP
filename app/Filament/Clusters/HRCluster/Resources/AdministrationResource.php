<?php

namespace App\Filament\Clusters\HRCluster\Resources;

use App\Filament\Clusters\HRCluster;
use App\Filament\Clusters\HRCluster\Resources\AdministrationResource\Pages;
use App\Filament\Clusters\HRCluster\Resources\AdministrationResource\RelationManagers;
use App\Models\Administration;
use App\Models\Branch;
use App\Models\Employee;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AdministrationResource extends Resource
{
    protected static ?string $model = Administration::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = HRCluster::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 3;
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make('')->columns(6)->label('')->schema([
                    TextInput::make('name')
                        ->label('Name')
                        ->required()
                        ->maxLength(255),
                    Toggle::make('is_global')
                        ->live()
                        ->helperText('If this is a global administration, it will be available to all branches')
                        ->default(1)->inline(false),

                    Select::make('branch_id')->searchable()
                        ->label('Branch')->live()
                        ->options(Branch::where('active', 1)
                            ->select('id', 'name')->get()->pluck('name', 'id'))
                        ->helperText('Choose branch')
                        ->visible(fn($get) => $get('is_global') == 0),
                    Select::make('manager_id')->searchable()
                        ->label('Manager')
                        ->options(function ($get) {
                            if ($get('is_global') == 1) {
                                return Employee::employeeTypesManagers()->active()
                                    ->select('id', 'name')->get()->pluck('name', 'id');
                            } else {
                                return Employee::forBranch($get('branch_id'))
                                    ->employeeTypesManagers()
                                    ->active()
                                    ->select('id', 'name')->get()->pluck('name', 'id');
                            }
                        })
                        ->helperText('Enter manager'),
                    Select::make('parent_id')->searchable()
                        ->label('Parent')
                        ->options(function ($get) {
                            if ($get('is_global') == 1) {
                                return Administration::global()
                                    ->select('id', 'name')->get()->pluck('name', 'id');
                            } else {
                                return Administration::forBranch($get('branch_id'))
                                    ->select('id', 'name')->get()->pluck('name', 'id');
                            }
                        })
                        ->helperText('Parent administration'),

                    Toggle::make('active')->default(1)->inline(false),



                    Textarea::make('description')
                        ->label('Description')->helperText('Enter description')
                        ->columnSpanFull()
                        ->nullable()
                        ->maxLength(500),
                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->striped()
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->searchable()
                    ->sortable()->toggleable()->searchable(),
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()->toggleable()->searchable(),

                TextColumn::make('description')
                    ->label('Description')
                    ->limit(25)->toggleable()->wrap(),

                TextColumn::make('manager.name')
                    ->label('Manager')
                    ->sortable()->searchable()->toggleable(),

                TextColumn::make('branch.name')
                    ->label('Branch')
                    ->sortable()->searchable()->toggleable(),
                TextColumn::make('parent.name')
                    ->label('Parent administration')
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label('Created At')->toggleable()
                    ->dateTime('d/m/Y H:i'),


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

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
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
            'index' => Pages\ListAdministrations::route('/'),
            'create' => Pages\CreateAdministration::route('/create'),
            'edit' => Pages\EditAdministration::route('/{record}/edit'),
        ];
    }
}
