<?php
namespace App\Filament\Traits\Forms;

use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Grid;
use App\Models\Branch;
use App\Models\Employee;
use Spatie\Permission\Models\Role as Role;
use App\Models\User;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;
trait HasEmployeeExistingForm
{
    private static function employeExistingForm()
    {
        return Fieldset::make()->visible(fn(Get $get) => $get('account_mode') === 'existing_employee')
            ->schema([

                Hidden::make('is_exist_employee')->default(1),
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
                                $set('nationality', $employee->nationality);
                                // $set('avatar', $employee->avatar);
                                // $set('avatar', [
                                //     'url' => Storage::disk('s3')->url($employee->avatar),
                                //     'name' => basename($employee->avatar),
                                // ]);
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
                        // ->validateFor(
                        //     country: 'MY',
                        //     lenient: true, // default: false
                        // )
                        ,

                    ]),
                    Fieldset::make()->columns(3)->label('Set user type, role and manager')->schema([
                        Select::make('user_type')->required()
                            ->label('User type')
                            ->options(getUserTypes())
                            ->live()
                            ->afterStateUpdated(function (Set $set, Get $get, ?string $state) {

                                //  dd($roles,$state);
                            }),
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
                            }),
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
                        Select::make('nationality')
                            ->label('Nationality')
                            ->options(getNationalities()) // Loads nationalities from JSON file
                            ->searchable()
                            ->nullable(),
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

            ]);

    }
}