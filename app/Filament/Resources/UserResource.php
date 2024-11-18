<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\User;
use App\Models\UserType;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;

// use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
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
                                fn(string $search): array=>
                                Employee::where('active', 1)
                                    ->whereDoesntHave('user')
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
                                    $set('branch_id', $employee->branch_id);
                                    $positionId = $employee?->position_id;
                                    if ($positionId == 2) {
                                        if (isset($employee?->branch_id)) {
                                            $branchManagerId = Branch::find($employee?->branch_id)?->user?->id;
                                            if ($branchManagerId) {
                                                $set('owner_id', $branchManagerId);
                                            }
                                        }
                                        $set('roles', [8]);
                                    }
                                }

                            })]),
                    Fieldset::make('')->label('')->schema([
                        Grid::make()->columns(3)->schema([
                            TextInput::make('name')->disabled()->unique(ignoreRecord: true),
                            TextInput::make('email')->disabled()->unique(ignoreRecord: true)->email()->required(),
                            PhoneInput::make('phone_number')->disabled()
                            // ->numeric()
                                ->initialCountry('MY')
                                ->onlyCountries([
                                    'MY',
                                    'US',
                                    'YE',
                                    'AE',
                                    'SA',
                                ])
                                ->displayNumberFormat(PhoneInputNumberType::E164)
                                ->autoPlaceholder('aggressive')
                                ->validateFor(
                                    country: 'MY',
                                    lenient: true, // default: false
                                ),

                        ]),
                        Fieldset::make()->columns(3)->label('Set user type, role and manager')->schema([
                            Select::make('user_type')->required()
                                ->label('User type')
                                ->options(getUserTypes())
                                ->live()
                                ->afterStateUpdated(function (Set $set, Get $get, ?string $state) {

                                    //  dd($roles,$state);
                                })
                            ,
                            CheckboxList::make('roles')
                                ->label('Role')
                                ->relationship('roles')->required()
                                ->maxItems(1)
                                ->options(function (Get $get) {
                                    // dd($get('user_type'),'hi');
                                    if ($get('user_type')) {
                                        $roles = getRolesByTypeId($get('user_type'));
                                        // dd($roles,gettype($roles));
                                        return Role::select('name', 'id')->whereIn('id', $roles)
                                            ->orderBy('name', 'asc')
                                            ->get()->pluck('name', 'id');
                                    }
                                })
                            ,
                            Select::make('owner_id')
                                ->label('Manager')
                                ->searchable()
                                ->options(function () {
                                    return User::query()->select('name', 'id')->get()->pluck('name', 'id');
                                }),
                            Select::make('branch_id')
                                ->label('Branch')
                                ->searchable()
                                ->disabled()
                                ->options(function () {
                                    return Branch::query()->get()->pluck('name', 'id');
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
                                ->label('Confirm Password'),
                        ]),

                    ]),

                ]),
                Fieldset::make()->visible(fn(Get $get): bool => !$get('is_exist_employee'))->schema([

                    Grid::make()->columns(3)->schema([
                        Fieldset::make()->label('Personal data')->schema([
                            TextInput::make('name')->required()->unique(ignoreRecord: true),
                            TextInput::make('email')->required()->unique(ignoreRecord: true)->email()->required(),
                            PhoneInput::make('phone_number')
                            // ->numeric()
                                ->initialCountry('MY')
                                ->onlyCountries([
                                    'MY',
                                    'US',
                                    'YE',
                                    'AE',
                                    'SA',
                                ])
                                ->required()
                                ->displayNumberFormat(PhoneInputNumberType::E164)
                                ->autoPlaceholder('aggressive')
                                ->validateFor(
                                    country: 'MY',
                                    lenient: true, // default: false
                                ),
                                Select::make('gender')
                                ->label('Gender')
                                ->options([
                                    1 => 'Male',
                                    0 => 'Female',
                                ])
                                ->default(1)
                                ->required(),
                            
                            Select::make('nationality')
                                ->label('Nationality')
                                ->options(getNationalities()) // Loads nationalities from JSON file
                                ->searchable()
                                ->nullable(),
                            Select::make('branch_id')
                                ->label('Branch')
                                ->required()
                                ->searchable()
                                ->options(function () {
                                    return Branch::where('active', 1)->select('name', 'id')->get()->pluck('name', 'id');
                                }),
                        ]),

                    ]),

                    Fieldset::make()->label('Set user type and role')->schema([
                        Select::make('user_type')
                            ->label('User type')
                            // ->options(getUserTypes())
                            ->options(
                                UserType::select('name', 'id')
                                ->whereNotIn('id', [2,3,4])
                                ->get()->pluck('name', 'id')
                            )
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set, Get $get, ?string $state) {

                                //  dd($roles,$state);
                            })
                        ,
                        CheckboxList::make('roles')->required()
                            ->label('Role')
                            ->relationship('roles')
                            ->maxItems(1)
                            ->options(function (Get $get) {
                                // dd($get('user_type'),'hi');
                                if ($get('user_type')) {
                                    $roles = getRolesByTypeId($get('user_type'));
                                    // dd($roles,gettype($roles));
                                    return Role::select('name', 'id')
                                        ->whereIn('id', $roles)
                                        ->orderBy('name', 'asc')
                                        ->get()->pluck('name', 'id');
                                }
                            })
                        ,
                    ]),
                    Grid::make()->columns(2)->schema([

                        Select::make('owner_id')
                            ->visible(function (Get $get) {
                                if (in_array($get('user_type'), [3, 4])) {
                                    return true;
                                }
                            })
                            ->label('Manager')
                            ->searchable()
                            ->options(function () {
                                return User::query()->select('name', 'id')->get()->pluck('name', 'id');
                            }),

                    ]),
                    Fieldset::make()->label('')->schema([
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
                                ->label('Confirm Password'),
                        ]),
                    ]),
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
                    ->searchable(isIndividual: true, isGlobal: false)
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('email')->icon('heroicon-m-envelope')->copyable()
                ->copyMessage('Email address copied')
                ->copyMessageDuration(1500)
                    ->sortable()->searchable()
                    ->searchable(isIndividual: true, isGlobal: false)
                    ->toggleable(isToggledHiddenByDefault: false)
                    ,

                TextColumn::make('phone_number')->label('Phone')->searchable()->icon('heroicon-m-phone')->searchable(isIndividual: true)
                    ->toggleable(isToggledHiddenByDefault: false)->default('_')->copyable()
                    ->copyable()
                    ->copyMessage('Phone number copied')
                    ->copyMessageDuration(1500)
                     ,

                TextColumn::make('branch.name')->searchable()->label('Branch')
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('owner.name')->searchable()->label('Manager')
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('first_role.name')->label('Role')
                    ->toggleable(isToggledHiddenByDefault: false)
                ,
                IconColumn::make('has_employee')->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-mark')
                    ->toggleable(isToggledHiddenByDefault: true)
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

    public static function canViewAny(): bool
    {
        return true;
    }
}
