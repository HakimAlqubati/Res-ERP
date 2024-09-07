<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\UserCluster;
use App\Filament\Resources\UserResource\Pages;
use App\Models\Employee;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
// use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use Illuminate\Support\Str;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'User & Roles';
    // protected static ?string $cluster = UserCluster::class;
    public static function getNavigationLabel(): string
    {
        return __('lang.users');
    }
    public static function form(Form $form): Form
    {

        return $form
            ->schema([

                Fieldset::make()->label('Check if you want to create a user account for existing employee')->schema([
                    Toggle::make('is_exist_employee')->label('')
                        ->inline(false)
                        ->live(),
                ])->hiddenOn('edit'),
                Fieldset::make()->visible(fn(Get $get): bool => $get('is_exist_employee'))->schema([


                    Fieldset::make()->schema([Select::make('search_employee')->label('Search for employee')
                        ->helperText('You can search using .. Employee (Name, Email, ID, Employee number)...')
                        // ->options(Employee::where('active', 1)->select('name', 'id', 'phone_number', 'email')->get()->pluck('name', 'id'))
                        ->getSearchResultsUsing(
                            fn(string $search): array =>
                            Employee::where('active', 1)
                                ->where(function ($query) use ($search) {
                                    $query->where('name', 'like', "%{$search}%")
                                        ->orWhere('email', 'like', "%{$search}%")
                                        ->orWhere('id', $search)
                                        ->orWhere('phone_number', 'like', "%{$search}%")
                                        ->orWhere('job_title', 'like', "%{$search}%");
                                })
                                ->limit(5)
                                ->pluck('name', 'id')
                                ->toArray()
                        )
                        ->getOptionLabelUsing(fn($value): ?string => Employee::find($value)?->name)
                        ->columnSpanFull()
                        ->searchable()
                        ->reactive()
                        ->afterStateUpdated(function (Set $set, Get $get, ?string $state) {

                            $employee = Employee::find($state);
                            if ($employee) {
                                $set('name', $employee->name);
                                $set('email', $employee->email);
                                $set('phone_number', $employee->phone_number);
                            }
                        }),]),
                    Fieldset::make('')->label('')->schema([
                        Grid::make()->columns(3)->schema([
                            TextInput::make('name')->disabled()->unique(ignoreRecord: true),
                            TextInput::make('email')->disabled()->unique(ignoreRecord: true)->email(),
                            TextInput::make('phone_number')->disabled()->unique(ignoreRecord: true)->numeric(),

                        ]),
                        Grid::make()->columns(2)->schema([
                            Select::make('roles')
                                ->label('Role')
                                ->relationship('roles', 'name')
                                ->multiple()
                                ->maxItems(1)
                                ->preload()
                                ->searchable(),

                            Select::make('owner_id')
                                ->label('Owner')
                                ->searchable()
                                ->options(function () {
                                    return DB::table('users')->pluck('name', 'id');
                                }),
                        ]),
                        Grid::make()->columns(2)->schema([
                            TextInput::make('password')
                                ->password()
                                ->required(fn(string $context) => $context === 'create')
                                ->reactive()
                                ->dehydrateStateUsing(fn($state) => Hash::make($state)),
                            TextInput::make('password_confirmation')
                                ->password()
                                ->required(fn(string $context) => $context === 'create')
                                ->same('password')
                                ->label('Confirm Password')
                        ]),

                    ]),

                ]),
                Fieldset::make()->visible(fn(Get $get): bool => !$get('is_exist_employee'))->schema([

                    Grid::make()->columns(3)->schema([
                        TextInput::make('name')->required()->unique(ignoreRecord: true),
                        TextInput::make('email')->required()->unique(ignoreRecord: true)->email()->required(),
                        TextInput::make('phone_number')->unique(ignoreRecord: true)->numeric(),

                    ]),

                    Grid::make()->columns(2)->schema([
                        Select::make('roles')
                            ->label('Role')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->maxItems(1)
                            ->preload()
                            ->searchable(),

                        Select::make('owner_id')
                            ->label('Owner')
                            ->searchable()
                            ->options(function () {
                                return DB::table('users')->pluck('name', 'id');
                            }),
                    ]),
                    Grid::make()->columns(2)->schema([
                        TextInput::make('password')
                            ->password()
                            ->required(fn(string $context) => $context === 'create')
                            ->reactive()
                            ->dehydrateStateUsing(fn($state) => Hash::make($state)),
                        TextInput::make('password_confirmation')
                            ->password()
                            ->required(fn(string $context) => $context === 'create')
                            ->same('password')
                            ->label('Confirm Password')
                    ])
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->sortable()->searchable()
                    ->searchable(isIndividual: true, isGlobal: false),
                ImageColumn::make('avatar_image')->label('')
                    ->circular(),
                TextColumn::make('name')
                    ->limit(20)
                    ->sortable()->searchable()
                    ->searchable(isIndividual: true, isGlobal: false),
                TextColumn::make('email')->icon('heroicon-m-envelope')
                    ->sortable()->searchable()->limit(20)
                    ->searchable(isIndividual: true, isGlobal: false),

                TextColumn::make('phone_number')->searchable()->icon('heroicon-m-phone')->searchable(isIndividual: true),

                TextColumn::make('owner.name')->searchable(),
                TextColumn::make('first_role.name')->label('Role'),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
