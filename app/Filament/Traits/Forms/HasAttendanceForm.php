<?php
namespace App\Filament\Traits\Forms;

use App\Models\Branch;
use App\Models\BranchArea;
use App\Models\User;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;
trait HasAttendanceForm
{

    private static function attendanceForm()
    {
        return Fieldset::make()
            ->visible(fn(Get $get) => $get('account_mode') === 'attendance_user')

            ->schema([

                Grid::make()->columns(3)->schema([
                    Fieldset::make()->label('')->schema([
                        Hidden::make('is_attendance_user')->default(1),
                        TextInput::make('name')->live(onBlur: true)
                            ->afterStateUpdated(fn($set, $state) => $set('attendanceDevice.name', $state))->required()->unique(ignoreRecord: true),
                        TextInput::make('email')->required()->unique(ignoreRecord: true)->email()->required(),

                        Select::make('branch_id')->label('Branch')
                            ->options(Branch::select('name', 'id')
                                    ->active()->normal()->activePopups()
                                    ->pluck('name', 'id'))
                            ->default(function () {
                                if (isStuff()) {
                                    return auth()->user()->branch_id;
                                }
                            })
                            ->live()
                            ->required(),
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
            ]);
    }
}