<?php

namespace App\Filament\Resources;

use Illuminate\Support\Str;
use App\Filament\Resources\UserResource\Pages;
use Illuminate\Validation\Rules\Password;
use App\Models\Branch;
use App\Models\BranchArea;
use App\Models\Employee;
use App\Models\LoginAttempt;
use App\Models\User;
use App\Models\UserType;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
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
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    public static function getNavigationLabel(): string
    {
        return __('lang.users');
    }
    public static function form(Form $form): Form
    {

        return $form
            ->schema([

                Fieldset::make()->label('')->columns(2)->schema([
                    Toggle::make('is_exist_employee')->label('')
                        ->inline(false)->helperText('Check if you want to create a user account for existing employee')
                        ->live(),
                    Toggle::make('is_attendance_user')->label('')
                        ->inline(false)->helperText('Check if you want to create a user account for attendance webcam')
                        ->live(),
                ])->hiddenOn('edit'),
                Fieldset::make()->visible(fn(Get $get): bool => $get('is_exist_employee'))
                    ->schema(formUserForExistingEmployee()),
                Fieldset::make()->visible(fn(Get $get): bool => !$get('is_exist_employee') && !$get('is_attendance_user'))->schema([

                    Grid::make()->columns(3)->schema([
                        Fieldset::make()->label('Personal data')->schema([
                            TextInput::make('name')->required()->unique(ignoreRecord: true),
                            TextInput::make('email')->required()->unique(ignoreRecord: true)->email()->required(),
                            Grid::make()->columns(3)->columnSpanFull()->schema([
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
                                    ->unique(ignoreRecord: true)
                                    ->displayNumberFormat(PhoneInputNumberType::E164)
                                    ->autoPlaceholder('aggressive')
                                // ->validateFor(
                                //     country: 'MY',
                                //     lenient: true, // default: false
                                // )
                                ,
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

                            ]),
                            CheckboxList::make('branches')->columns(3)
                                ->relationship('branches')
                                ->label('Branches')->columnSpanFull()->searchable()
                                ->options(Branch::active()->get(['name', 'id'])
                                    // ->withAccess()
                                    ->pluck('name', 'id'))->bulkToggleable(),


                            Select::make('branch_id')
                                ->label('Default Branch')
                                ->required()
                                ->visible(function (Get $get) {
                                    $roles = $get('roles') ?? [];
                                    return !in_array(5, $roles);
                                })
                                ->searchable()
                                ->options(function ($get) {
                                    return Branch::where('active', 1)
                                        ->whereIn('id', $get('branches'))

                                        ->select('name', 'id')->get()->pluck('name', 'id');
                                }),
                        ]),

                    ]),

                    Fieldset::make()->label('Set user type and role')->schema([
                        Select::make('user_type')
                            ->label('User type')
                            // ->options(getUserTypes())
                            ->options(
                                UserType::select('name', 'id')
                                    // ->whereNotIn('id', [2,3,4])
                                    ->get()->pluck('name', 'id')
                            )
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set, Get $get, ?string $state) {

                                //  dd($roles,$state);
                            }),
                        CheckboxList::make('roles')->required()
                            ->label('Roles')
                            ->relationship('roles')
                            // ->maxItems(1)
                            ->live()
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
                            }),
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
                            setting('password_contains_for') == 'easy_password' ?
                                TextInput::make('password')
                                ->password()
                                ->required(fn(string $context) => $context === 'create')
                                ->reactive()
                                ->dehydrateStateUsing(fn($state) => Hash::make($state))

                                : TextInput::make('password')
                                ->label('Password')
                                ->password()
                                ->required(fn(string $context) => $context === 'create')
                                ->reactive()
                                ->dehydrateStateUsing(fn($state) => Hash::make($state))
                                ->rules([
                                    'required',
                                    'string',
                                    Password::min(setting('password_min_length'))
                                        ->mixedCase()
                                        ->numbers()
                                        ->symbols()
                                        ->uncompromised(),
                                ])
                                ->helperText(__('lang.password_requirements', ['min' => setting('password_min_length')])),
                            // TextInput::make('password')
                            //     ->password()
                            //     ->required(fn(string $context) => $context === 'create')
                            //     ->reactive()
                            //     ->dehydrateStateUsing(fn($state) => Hash::make($state)),
                            TextInput::make('password_confirmation')
                                ->password()
                                ->required(fn(string $context) => $context === 'create')
                                ->same('password')
                                ->label('Confirm Password'),
                        ]),
                    ]),
                ]),
                Fieldset::make()
                    ->visible(fn(Get $get): bool =>  $get('is_attendance_user'))->schema([

                        Grid::make()->columns(3)->schema([
                            Fieldset::make()->label('')->schema([
                                TextInput::make('name')->live()->afterStateUpdated(fn($set, $state) => $set('attendanceDevice.name', $state))->required()->unique(ignoreRecord: true),
                                TextInput::make('email')->required()->unique(ignoreRecord: true)->email()->required(),


                                Select::make('branch_area_id')->label('Branch area')
                                    ->options(function (Get $get) {
                                        return BranchArea::query()
                                            ->where('branch_id', $get('branch_id'))
                                            ->pluck('name', 'id');
                                    }),
                            ]),

                        ]),
                        Fieldset::make()->relationship('attendanceDevice')->schema([
                            TextInput::make('name')->columnSpanFull()->label('Device Name'),
                            Textarea::make('description')->columnSpanFull()->label('Device Description'),
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
            ->striped()
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->sortable()->searchable()

                    ->searchable(isIndividual: true, isGlobal: false)->toggleable(isToggledHiddenByDefault: true),
                ImageColumn::make('avatar_image')->label('')
                    ->circular()->alignCenter(true),
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
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('phone_number')->label('Phone')->searchable()->icon('heroicon-m-phone')->searchable(isIndividual: true)
                    ->toggleable(isToggledHiddenByDefault: false)->default('_')->copyable()
                    ->copyable()
                    ->copyMessage('Phone number copied')
                    ->copyMessageDuration(1500),

                TextColumn::make('branch.name')->searchable()->label('Branch')
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('owner.name')->searchable()->label('Manager')
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('first_role.name')->label('Role')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('roles_title')->label('Roles')
                    ->toggleable(isToggledHiddenByDefault: false),
                IconColumn::make('has_employee')->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-mark')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('fcm_token')
                    ->label('FCM Token')->color(Color::Green)
                    ->copyable()->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_blocked')
                    ->boolean()->alignCenter(true)
                    ->label(__("lang.is_blocked"))->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('last_login_at')->label('Last Login')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('active')
                    ->query(fn(Builder $query): Builder => $query->whereNotNull('active')),
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('role')
                    ->label('Filter by Role')
                    ->options(Role::query()->pluck('name', 'id')->toArray()) // Fetch roles as options
                    ->query(function (Builder $query, $data) {
                        if (!empty($data['value'])) { // Check if a role is selected
                            return $query->whereHas('roles', function ($q) use ($data) {
                                $q->where('id', $data['value']); // Filter users by the selected role
                            });
                        }
                        return $query; // Return the query unchanged if no role is selected
                    }),
                Tables\Filters\SelectFilter::make('branch_id')
                    ->searchable()
                    ->multiple()
                    ->label(__('lang.branch'))->options(
                        Branch::withAllTypes()
                            ->pluck('name', 'id')->toArray()
                    )
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                    // Add a custom action for updating password
                    Tables\Actions\Action::make('updatePassword')
                        ->form([
                            TextInput::make('password')
                                ->label('New Password')
                                ->password()
                                ->required()
                                ->minLength(6)
                                ->suffixIcon('heroicon-o-lock-closed'),
                            TextInput::make('password_confirmation')
                                ->label('Confirm New Password')
                                ->password()
                                ->required()->suffixIcon('heroicon-o-lock-closed')
                                ->same('password'),
                        ])
                        ->action(function (User $user, array $data): void {
                            // Update the user's password
                            $user->update([
                                'password' => Hash::make($data['password']),
                            ]);
                        })
                        ->icon('heroicon-s-lock-closed') // Optional: Add an icon
                        ->label('Update Password'), // Optional: Add a label
                    Tables\Actions\Action::make("allowLogin")
                        ->label(__('lang.allow_login'))
                        ->color('success')
                        // ->icon('heroicon-o-ban')
                        ->action(function ($record) {
                            try {
                                LoginAttempt::where('email', $record->email)
                                    ->where('successful', false)->delete();
                                showSuccessNotifiMessage('Done');
                            } catch (\Exception $e) {
                                // Log the exception for debugging
                                Log::error('Error clearing login attempts', ['exception' => $e]);

                                showWarningNotifiMessage('Faild', $e->getMessage());
                            }
                        })->requiresConfirmation()->hidden(),
                ])
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\ForceDeleteBulkAction::make(),
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

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\ListUsers::class,
            Pages\CreateUser::class,
            Pages\EditUser::class,
        ]);
    }


    public static function getNavigationBadge(): ?string
    {
        return User::query()->withBranch()->count();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withBranch()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])->withMax('loginHistories as last_login_at', 'created_at');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view_any_user') || auth()->user()->can('view_own_profile');
    }
    public static function canAccess(): bool
    {
        return static::canViewAny();
    }
}
