<?php
namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Traits\Forms\HasAttendanceForm;
use App\Filament\Traits\Forms\HasEmployeeExistingForm;
use App\Filament\Traits\Forms\HasNewUserForm;
use App\Models\Branch;
use App\Models\LoginAttempt;
use App\Models\User;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Alignment;
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
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;

// use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class UserResource extends Resource
{
    use HasAttendanceForm, HasNewUserForm, HasEmployeeExistingForm;
    protected static ?string $model           = User::class;
    protected static ?string $navigationIcon  = 'heroicon-o-users';
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

                ToggleButtons::make('account_mode')
                    ->label('')
                    ->inline()
                    ->options([
                        'existing_employee' => 'Existing Employee',
                        'attendance_user'   => 'Attendance User',
                        'new_user'          => 'New User',
                    ])
                    ->icons([
                        'existing_employee' => 'heroicon-o-identification',
                        'attendance_user'   => 'heroicon-o-camera',
                        'new_user'          => 'heroicon-o-user-plus',
                    ])
                    ->default('new_user')
                    ->live()
                    ->required() ,
                // Fieldset::make()->label('')->columns(2)->schema([
                //     Toggle::make('is_exist_employee')->label('')
                //         ->inline(false)->helperText('Check if you want to create a user account for existing employee')
                //         ->live(),
                //     Toggle::make('is_attendance_user')->label('')
                //         ->inline(false)->helperText('Check if you want to create a user account for attendance webcam')
                //         ->live(),
                // ])->hiddenOn('edit'),
                self::employeExistingForm(),
                self::newUserForm(),
                self::attendanceForm(),
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
                        if (! empty($data['value'])) { // Check if a role is selected
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
                ),
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                    Tables\Actions\Action::make('quickEdit')
                        ->label('Quick Edit')
                        ->icon('heroicon-o-pencil-square')
                        ->form([

                            Fieldset::make()->columns(2)->schema([

                                TextInput::make('name')
                                    ->required()
                                    ->default(fn($record) => $record->name)
                                    ->unique(ignoreRecord: true),
                                PhoneInput::make('phone_number')
                                    ->label('Phone')
                                    ->required()
                                    ->default(fn($record) => $record->phone_number)
                                    ->unique(ignoreRecord: true)
                                    ->initialCountry('MY')
                                    ->onlyCountries(['MY', 'US', 'YE', 'AE', 'SA'])
                                    ->displayNumberFormat(PhoneInputNumberType::E164),
                                TextInput::make('email')
                                    ->email()
                                    ->default(fn($record) => $record->email)
                                    ->required()
                                    ->unique(ignoreRecord: true),
                                Select::make('branch_id')
                                    ->label('Branch')
                                    ->searchable()
                                    ->default(fn($record) => $record->branch_id)
                                    ->options(
                                        fn() => Branch::active()

                                            ->pluck('name', 'id')
                                    )
                                    ->required(),
                            ]),
                        ])
                        ->action(function (User $record, array $data): void {
                            try {
                                if ($record->update($data)) {
                                    showSuccessNotifiMessage('User updated successfully');
                                } else {
                                    showWarningNotifiMessage('No changes made to the user');
                                }
                            } catch (\Throwable $th) {
                                throw $th;
                                showWarningNotifiMessage('Failed to update user', $th->getMessage());
                            }
                        }),
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
                        ->label('Update Password'),      // Optional: Add a label
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
                ]),
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
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
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
        return static::getModel()::count();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])->withMax('loginHistories as last_login_at', 'created_at');
    }

    public static function canViewAny(): bool
    {
        return true;
    }
}