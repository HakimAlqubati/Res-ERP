<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SettingResource\Pages;
use App\Models\Attendance;
use App\Models\Setting;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;

class SettingResource extends Resource
{
    protected static ?string $model = Setting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('settings')->columnSpanFull()
                    ->tabs([
                        Tab::make('Company Info')
                            ->icon('heroicon-o-building-office')
                            ->schema([
                                Fieldset::make()->columns(4)->label('Company Info')->schema([
                                    TextInput::make("company_name")
                                    ->label('Name'),
                                    TextInput::make("company_phone")
                                    ->label('Name')
                                    
                                    ->required(),
                                FileUpload::make('company_logo')
                                    ->label('Logo')->required()
                                    ->image()
                                    ->columnSpan(2)
                                    ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                                        return "company_logo/" . Str::random(15) . "." . $file->getClientOriginalExtension();
                                    }),
                                    Fieldset::make()->columnSpanFull()->label('Address')->schema([
                                        Textarea::make('address')->label('')->columnSpanFull()->required(),
                                    ])
                                ])
                            ]),

                        Tab::make('HR Settings')
                        ->icon('heroicon-o-user-group')
                            ->schema([
                                Fieldset::make()->label('Work Shifts')->columns(3)->schema([
                                    TextInput::make("hours_count_after_period_before")
                                        ->label('Hours allowed before period')
                                        ->numeric()
                                        ->required(),
                                    TextInput::make("hours_count_after_period_after")
                                        ->label('Hours allowed after period')
                                        ->numeric()
                                        ->required(),
                                    

                                    Fieldset::make()->columns(1)->columnSpan(1)->schema([
                                        Select::make("period_allowed_to_calculate_overtime")
                                            ->label('Overtime calculation period')
                                            ->options([
                                                Attendance::PERIOD_ALLOWED_OVERTIME_QUARTER_HOUR => Attendance::PERIOD_ALLOWED_OVERTIME_QUARTER_HOUR_LABEL,
                                                Attendance::PERIOD_ALLOWED_OVERTIME_HALF_HOUR => Attendance::PERIOD_ALLOWED_OVERTIME_HALF_HOUR_LABEL,
                                                Attendance::PERIOD_ALLOWED_OVERTIME_HOUR => Attendance::PERIOD_ALLOWED_OVERTIME_HOUR_LABEL,
                                            ])
                                            ->live()
                                            ->required(),
                                        Toggle::make('calculating_overtime_with_half_hour_after_hour')
                                            ->visible(fn(Get $get): bool => $get('period_allowed_to_calculate_overtime') == Attendance::PERIOD_ALLOWED_OVERTIME_HOUR)
                                        ,

                                    ]),
                                    TextInput::make("early_attendance_minutes")
                                    ->label('Minutes counted as early arrival for attendance')
                                    ->helperText('The number of minutes before the scheduled start time that is considered early attendance.')
                                    ->numeric()
                                    ->required(),
                                    TextInput::make("pre_end_hours_for_check_in_out")
                                    ->label('Hours before period end for action')
                                    ->helperText('Number of hours remaining before period end to trigger an action if check-in or check-out is not recorded')
                                    ->numeric()
                                    ->required(),
                                ]),
                                Fieldset::make()->label('Salary')->columns(4)->schema([
                                    TextInput::make('days_in_month')->label('Days in Month')->helperText('Days of month to calculate daily salary')->required(),
                                ]),

                                
                            ]),
                            Tab::make('Tax Settings')
                            ->icon('heroicon-o-calculator')
                            ->schema([

                                Fieldset::make('MTD/PCB Tax Brackets')->columns(4)->schema([
                                    TextInput::make('bracket_1')
                                        ->label('0 - 5,000')
                                        ->default(0) // 0% tax rate
                                        ->helperText('Tax Rate: 0%')
                                        ->disabled(),
                                    TextInput::make('bracket_2')
                                        ->label('5,001 - 20,000')
                                        ->default(1) // 1% tax rate
                                        ->helperText('Tax Rate: 1%')
                                        ->numeric()
                                        ->required(),
                                    TextInput::make('bracket_3')
                                        ->label('20,001 - 35,000')
                                        ->default(3) // 3% tax rate
                                        ->helperText('Tax Rate: 3%')
                                        ->numeric()
                                        ->required(),
                                    TextInput::make('bracket_4')
                                        ->label('35,001 - 50,000')
                                        ->default(8) // 8% tax rate
                                        ->helperText('Tax Rate: 8%')
                                        ->numeric()
                                        ->required(),
                                    TextInput::make('bracket_5')
                                        ->label('50,001 - 70,000')
                                        ->default(13) // 13% tax rate
                                        ->helperText('Tax Rate: 13%')
                                        ->numeric()
                                        ->required(),
                                    TextInput::make('bracket_6')
                                        ->label('70,001 - 100,000')
                                        ->default(21) // 21% tax rate
                                        ->helperText('Tax Rate: 21%')
                                        ->numeric()
                                        ->required(),
                                    TextInput::make('bracket_7')
                                        ->label('100,001 - 250,000')
                                        ->default(24) // 24% tax rate
                                        ->helperText('Tax Rate: 24%')
                                        ->numeric()
                                        ->required(),
                                    TextInput::make('bracket_8')
                                        ->label('250,001 - 400,000')
                                        ->default(25) // 25% tax rate
                                        ->helperText('Tax Rate: 25%')
                                        ->numeric()
                                        ->required(),
                                    TextInput::make('bracket_9')
                                        ->label('400,001 - 600,000')
                                        ->default(26) // 26% tax rate
                                        ->helperText('Tax Rate: 26%')
                                        ->numeric()
                                        ->required(),
                                    TextInput::make('bracket_10')
                                        ->label('600,001 - 1,000,000')
                                        ->default(28) // 28% tax rate
                                        ->helperText('Tax Rate: 28%')
                                        ->numeric()
                                        ->required(),
                                    TextInput::make('bracket_11')
                                        ->label('1,000,001 - 2,000,000')
                                        ->default(30) // 30% tax rate
                                        ->helperText('Tax Rate: 30%')
                                        ->numeric()
                                        ->required(),
                                    TextInput::make('bracket_12')
                                        ->label('Above 2,000,000')
                                        ->default(32) // 32% tax rate
                                        ->helperText('Tax Rate: 32%')
                                        ->numeric()
                                        ->required(),
                                ])
                            
                        ]),
                    ]),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            // 'index' => Pages\ListSettings::route('/'),
            // 'create' => Pages\CreateSetting::route('/create'),
            'index' => Pages\CreateSetting::route('/'),
            // 'edit' => Pages\EditSetting::route('/'),
        ];
    }

}
