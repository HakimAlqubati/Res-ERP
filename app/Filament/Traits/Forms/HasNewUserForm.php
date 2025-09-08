<?php

namespace App\Filament\Traits\Forms;

use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Set;
use App\Models\Branch;
use Spatie\Permission\Models\Role as Role;
use App\Models\User;
use App\Models\UserType;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;

trait HasNewUserForm
{

    private static function newUserForm()
    {
        return Fieldset::make()->columnSpanFull()
            ->visible(function (Get $get, $record) {
                if (! is_null($record)) {
                    return true;
                }
                if ($get('account_mode') === 'new_user') {
                    return true;
                }
                return false;
            })
            ->schema([

                Grid::make()->columns(3)->columnSpanFull()->schema([
                    Fieldset::make()->columnSpanFull()->label('Personal data')->schema([
                        TextInput::make('name')->required()->unique(ignoreRecord: true),
                        TextInput::make('email')->required()->unique(ignoreRecord: true)->email()->required(),
                        TextInput::make('phone_number')
                            ->unique(ignoreRecord: true)
                            ->columnSpan(1)

                            // ->numeric()
                            ->maxLength(14)->minLength(8),
                        Select::make('gender')
                            ->label('Gender')
                            ->options([
                                1 => 'Male',
                                0 => 'Female',
                            ])
                            // ->default(1)
                            ->required(),

                        Select::make('nationality')
                            ->label('Nationality')
                            ->options(getNationalities()) // Loads nationalities from JSON file
                            ->searchable()
                            ->nullable(),
                        Select::make('branch_id')
                            ->label('Branch')
                            ->required()
                            ->visible(function (Get $get) {
                                $roles = $get('roles') ?? [];
                                return ! in_array(5, $roles);
                            })
                            ->searchable()
                            ->options(function () {
                                return Branch::where('active', 1)->select('name', 'id')->get()->pluck('name', 'id');
                            }),

                    ]),

                ]),

                Fieldset::make()->columnSpanFull()->label('Set user type and role')->schema([
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
                        ->relationship(
                            name: 'roles',
                            titleAttribute: 'name',
                            modifyQueryUsing: function (Builder $query, Get $get) {
                                $allowed = getRolesByTypeId($get('user_type') ?? null) ?? [];
                                $query->where('guard_name', 'web')   // عدّل الحارس إذا لزم
                                    ->whereIn('id', $allowed);
                            }
                        )
                        ->validationAttribute('Roles')
                        ->validationMessages([
                            // Error triggered on each item: roles.*.in
                            '*.in'     => 'The selected role is not valid for the current user type. Please choose only from the available roles or change the user type.',
                            // Fallback if error comes on the whole array
                            'in'       => 'One of the selected roles is not valid for the current user type.',
                            'array'    => 'The roles list format is invalid.',
                            'required' => 'Please select at least one role.',
                        ])

                        // ->helperText('الأدوار المعروضة تعتمد على نوع المستخدم. عند تغيير "User type" قد تصبح بعض الأدوار غير متاحة.')
                        // ->maxItems(1)
                        ->live()
                    // ->options(function (Get $get) {
                    //     // dd($get('user_type'),'hi');
                    //     if ($get('user_type')) {
                    //         $roles = getRolesByTypeId($get('user_type'));
                    //         // dd($roles,gettype($roles));
                    //         return Role::select('name', 'id')
                    //             ->whereIn('id', $roles)
                    //             ->orderBy('name', 'asc')
                    //             ->get()->pluck('name', 'id');
                    //     }
                    // })
                    ,
                ]),
                Grid::make()->columnSpanFull()->columns(2)->schema([

                    Select::make('owner_id')
                        ->visible(function (Get $get) {
                            if (in_array($get('user_type'), [3, 4])) {
                                return true;
                            }
                        })
                        ->label('Manager')
                        ->searchable()
                        ->options(function ($get) {
                            $branchId = $get('branch_id');
                            if (! $branchId) {
                                return [];
                            }

                            return User::where('branch_id', $branchId)->whereIn('user_type', [1, 2, 3])
                                ->select('id', 'name')
                                ->pluck('name', 'id');
                        }),

                ]),
                Fieldset::make()->columnSpanFull()->label('')->schema([
                    Grid::make()->columns(2)->columnSpanFull()->schema([
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
                        TextInput::make('password_confirmation')
                            ->password()
                            ->required(fn(string $context) => $context === 'create')
                            ->same('password')
                            ->label('Confirm Password'),
                    ]),
                ]),
            ]);
    }
}
