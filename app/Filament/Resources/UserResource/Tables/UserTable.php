<?php

namespace App\Filament\Resources\UserResource\Tables;


use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Schemas\Schema;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\Action;
use Filament\Schemas\Components\Fieldset;
use Throwable;
use Exception;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages;
use App\Filament\Tables\Columns\SoftDeleteColumn;
use App\Filament\Traits\Forms\HasAttendanceForm;
use App\Filament\Traits\Forms\HasEmployeeExistingForm;
use App\Filament\Traits\Forms\HasNewUserForm;
use App\Models\Branch;
use App\Models\LoginAttempt;
use App\Models\User;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Alignment;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;

class UserTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->striped()
            ->defaultSort('id', 'desc')
            ->columns([
                SoftDeleteColumn
                    ::make(),
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
                    ->searchable(isIndividual: true, isGlobal: true)
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('phone_number')->label('Phone')->searchable()->icon('heroicon-m-phone')->searchable(isIndividual: true)
                    ->toggleable(isToggledHiddenByDefault: false)->default('_')->copyable()
                    ->copyable()
                    ->copyMessage('Phone number copied')
                    ->copyMessageDuration(1500),

                IconColumn::make('active')
                    ->label('Active')
                    ->boolean()
                    ->getStateUsing(fn($record) => $record->active ?? true) // ✅ يعامل null كـ true

                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),


                TextColumn::make('branch.name')->searchable()->label('Branch')
                    ->toggleable(isToggledHiddenByDefault: false)->limit(20)
                    ->tooltip(fn($state) => $state),
                TextColumn::make('owner.name')->searchable()
                    ->label('Manager')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('first_role.name')->label('Role')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('roles_title')->label('Roles')->limit(20)->tooltip(fn($state) => $state)
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('has_employee')->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-mark')->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('fcm_token')
                    ->label('FCM Token')->color(Color::Green)
                    ->copyable()->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_blocked')
                    ->boolean()->alignCenter(true)
                    ->label(__("lang.is_blocked"))->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('last_login_at')->label('Last Login')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('activities_count')
                    ->label(__('lang.activities_count'))
                    ->counts('activities')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('last_activity')
                    ->label(__('lang.last_activity'))
                    ->formatStateUsing(function ($state) {
                        if (!$state) {
                            return '-';
                        }
                        $date = \Carbon\Carbon::parse($state);
                        if ($date->isToday()) {
                            return $date->format('h:i A');
                        } elseif ($date->isYesterday()) {
                            return 'Yesterday ' . $date->format('h:i A');
                        } else {
                            if ($date->year === now()->year) {
                                return $date->format('M d, h:i A');
                            }
                            return $date->format('M d, Y h:i A');
                        }
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filtersFormColumns(2)
            ->filters([
                Filter::make('me')
                    ->label(__('lang.me'))
                    ->toggle()
                    ->query(fn(Builder $query) => $query->where('id', auth()->id())),
                TrashedFilter::make(),
                SelectFilter::make('active')
                    ->label('Status')
                    ->options([
                        1 => 'Active',
                        0 => 'Inactive',
                    ]),
                SelectFilter::make('role')
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
                SelectFilter::make('branch_id')
                    ->searchable()
                    ->multiple()
                    ->label(__('lang.branch'))->options(
                        Branch::withAllTypes()
                            ->forBranchManager('id')
                            ->pluck('name', 'id')->toArray()
                    ),
            ], FiltersLayout::Modal)
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                    RestoreAction::make(),
                    Action::make('createEmployee')
                        ->label('Create Employee')
                        ->icon('heroicon-o-user-plus')
                        ->color('success')
                        ->visible(fn(User $record) => ! $record->has_employee) // فقط اللي ما عنده موظف
                        ->schema([
                            Fieldset::make()->columnSpanFull()->columns(2)->schema([

                                TextInput::make('name')
                                    ->label('Full Name')
                                    ->default(fn($record) => $record->name)
                                    ->disabled(), // غير قابل للتعديل
                                TextInput::make('job_title')
                                    ->label('Job Title')
                                    ->required(),
                            ])
                        ])
                        ->action(function (User $record, array $data) {
                            $record->createLinkedEmployee($data);
                        }),


                    Action::make('quickEdit')
                        ->label('Quick Edit')
                        ->icon('heroicon-o-pencil-square')
                        ->schema([

                            Fieldset::make()->columns(2)->schema([

                                TextInput::make('name')
                                    ->required()
                                    ->default(fn($record) => $record->name)
                                    ->unique(ignoreRecord: true),
                                TextInput::make('phone_number')
                                    ->unique(ignoreRecord: true)
                                    ->columnSpan(1)

                                    // ->numeric()
                                    ->maxLength(14)->minLength(8),
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
                                            ->forBranchManager('id')
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
                            } catch (Throwable $th) {
                                throw $th;
                                showWarningNotifiMessage('Failed to update user', $th->getMessage());
                            }
                        })->hidden(),
                    // Add a custom action for updating password
                    Action::make('updatePassword')
                        ->schema([
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
                    Action::make("allowLogin")
                        ->label(__('lang.allow_login'))
                        ->color('success')
                        // ->icon('heroicon-o-ban')
                        ->action(function ($record) {
                            try {
                                LoginAttempt::where('email', $record->email)
                                    ->where('successful', false)->delete();
                                showSuccessNotifiMessage('Done');
                            } catch (Exception $e) {
                                // Log the exception for debugging

                                showWarningNotifiMessage('Faild', $e->getMessage());
                            }
                        })->requiresConfirmation()->hidden(),
                ]),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
                ForceDeleteBulkAction::make(),
                // ExportBulkAction::make(),
                RestoreBulkAction::make(),
            ]);
    }
}
