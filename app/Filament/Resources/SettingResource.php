<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SettingResource\Pages;
use App\Models\Attendance;
use App\Models\Setting;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\FileUpload;
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

class SettingResource extends Resource
{
    protected static ?string $model = Setting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $modelLabel = 'System Settings';
    protected static ?string $pluralLabel = 'System Settings';
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('settings')->columnSpanFull()
                    ->tabs([
                        Tab::make('Company Info')->hidden(function () {
                            if (isFinanceManager()) {
                                return true;
                            }
                            return false;
                        })
                            ->icon('heroicon-o-building-office')
                            ->schema([
                                Fieldset::make()->columns(4)->label('Company Info')->schema([
                                    TextInput::make("company_name")
                                        ->label('Name'),
                                    TextInput::make("company_phone")
                                        ->label('Phone Number')

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
                                    ]),
                                ]),
                            ]),

                        Tab::make('HR Settings')
                            ->icon('heroicon-o-user-group')
                            ->schema([
                                Fieldset::make()->label('Work Shifts')->columns(4)->schema([
                                    TextInput::make("hours_count_after_period_before")
                                        ->label('Allowed hours pre-period')
                                        ->numeric()
                                        ->required(),
                                    TextInput::make("hours_count_after_period_after")
                                        ->label('Allowed hours post-period')
                                        ->numeric()
                                        ->required(),

                                    TextInput::make("early_attendance_minutes")
                                        ->label('Early arrival minutes')
                                    // ->helperText('The number of minutes before the scheduled start time that is considered early attendance.')
                                        ->numeric()
                                        ->required(),
                                    TextInput::make("pre_end_hours_for_check_in_out")
                                        ->label('Pre-period action hours')
                                    // ->helperText('Number of hours remaining before period end to trigger an action if check-in or check-out is not recorded')
                                        ->numeric()
                                        ->required(),
                                    Fieldset::make()->columns(2)->columnSpanFull()->schema([
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
                                            ->inline(false)
                                            ->visible(fn(Get $get): bool => $get('period_allowed_to_calculate_overtime') == Attendance::PERIOD_ALLOWED_OVERTIME_HOUR)
                                        ,

                                    ]),
                                ]),
                                Fieldset::make()->label('Salary')->columns(4)->schema([
                                    TextInput::make('days_in_month')->label('Days in Month')->helperText('Days of month to calculate daily salary')->required(),
                                    TextInput::make('hours_no_in_day')->label('Hours No in Day')->helperText('Hours number in day to calculate hourly salary')->required(),
                                    TextInput::make('overtime_hour_multiplier')
                                        ->label('Overtime Hour Multiplier')
                                        ->helperText('Enter the overtime multiplier, e.g., 2 for double, 3 for triple')
                                        ->numeric()
                                        ->minValue(1)
                                        ->placeholder('Enter multiplier (e.g., 2, 3, 4)')
                                        ->required(),
                                ]),

                            ]),
                        Tab::make('Tax Settings')
                            ->icon('heroicon-o-calculator')
                            ->schema([
                                Fieldset::make('MTD/PCB Tax Brackets')->columns(4)->schema([
                                    TextInput::make('tax_bracket_0_to_5000')
                                        ->label('0 - 5,000')
                                        ->default(0) // 0% tax rate
                                        ->helperText(fn($state) => "Tax Rate: {$state}%")
                                        ->disabled(),

                                    TextInput::make('tax_bracket_5001_to_20000')
                                        ->label('5,001 - 20,000')
                                        ->default(1) // 1% tax rate
                                        ->helperText(fn($state) => "Tax Rate: {$state}%")
                                        ->numeric()
                                        ->required()
                                        ->reactive()
                                        ->afterStateUpdated(fn($state, callable $set) => $set('helperText', "Tax Rate: {$state}%")),

                                    TextInput::make('tax_bracket_20001_to_35000')
                                        ->label('20,001 - 35,000')
                                        ->default(3) // 3% tax rate
                                        ->helperText(fn($state) => "Tax Rate: {$state}%")
                                        ->numeric()
                                        ->required()
                                        ->reactive()
                                        ->afterStateUpdated(fn($state, callable $set) => $set('helperText', "Tax Rate: {$state}%")),

                                    TextInput::make('tax_bracket_35001_to_50000')
                                        ->label('35,001 - 50,000')
                                        ->default(8) // 8% tax rate
                                        ->helperText(fn($state) => "Tax Rate: {$state}%")
                                        ->numeric()
                                        ->required()
                                        ->reactive()
                                        ->afterStateUpdated(fn($state, callable $set) => $set('helperText', "Tax Rate: {$state}%")),

                                    TextInput::make('tax_bracket_50001_to_70000')
                                        ->label('50,001 - 70,000')
                                        ->default(13) // 13% tax rate
                                        ->helperText(fn($state) => "Tax Rate: {$state}%")
                                        ->numeric()
                                        ->required()
                                        ->reactive()
                                        ->afterStateUpdated(fn($state, callable $set) => $set('helperText', "Tax Rate: {$state}%")),

                                    TextInput::make('tax_bracket_70001_to_100000')
                                        ->label('70,001 - 100,000')
                                        ->default(21) // 21% tax rate
                                        ->helperText(fn($state) => "Tax Rate: {$state}%")
                                        ->numeric()
                                        ->required()
                                        ->reactive()
                                        ->afterStateUpdated(fn($state, callable $set) => $set('helperText', "Tax Rate: {$state}%")),

                                    TextInput::make('tax_bracket_100001_to_250000')
                                        ->label('100,001 - 250,000')
                                        ->default(24) // 24% tax rate
                                        ->helperText(fn($state) => "Tax Rate: {$state}%")
                                        ->numeric()
                                        ->required()
                                        ->reactive()
                                        ->afterStateUpdated(fn($state, callable $set) => $set('helperText', "Tax Rate: {$state}%")),

                                    TextInput::make('tax_bracket_250001_to_400000')
                                        ->label('250,001 - 400,000')
                                        ->default(25) // 25% tax rate
                                        ->helperText(fn($state) => "Tax Rate: {$state}%")
                                        ->numeric()
                                        ->required()
                                        ->reactive()
                                        ->afterStateUpdated(fn($state, callable $set) => $set('helperText', "Tax Rate: {$state}%")),

                                    TextInput::make('tax_bracket_400001_to_600000')
                                        ->label('400,001 - 600,000')
                                        ->default(26) // 26% tax rate
                                        ->helperText(fn($state) => "Tax Rate: {$state}%")
                                        ->numeric()
                                        ->required()
                                        ->reactive()
                                        ->afterStateUpdated(fn($state, callable $set) => $set('helperText', "Tax Rate: {$state}%")),

                                    TextInput::make('tax_bracket_600001_to_1000000')
                                        ->label('600,001 - 1,000,000')
                                        ->default(28) // 28% tax rate
                                        ->helperText(fn($state) => "Tax Rate: {$state}%")
                                        ->numeric()
                                        ->required()
                                        ->reactive()
                                        ->afterStateUpdated(fn($state, callable $set) => $set('helperText', "Tax Rate: {$state}%")),

                                    TextInput::make('tax_bracket_1000001_to_2000000')
                                        ->label('1,000,001 - 2,000,000')
                                        ->default(30) // 30% tax rate
                                        ->helperText(fn($state) => "Tax Rate: {$state}%")
                                        ->numeric()
                                        ->required()
                                        ->reactive()
                                        ->afterStateUpdated(fn($state, callable $set) => $set('helperText', "Tax Rate: {$state}%")),

                                    TextInput::make('tax_bracket_above_2000000')
                                        ->label('Above 2,000,000')
                                        ->default(32) // 32% tax rate
                                        ->helperText(fn($state) => "Tax Rate: {$state}%")
                                        ->numeric()
                                        ->required()
                                        ->reactive()
                                        ->afterStateUpdated(fn($state, callable $set) => $set('helperText', "Tax Rate: {$state}%")),
                                ]),
                            ]),
                        Tab::make('Task Settings')
                            ->icon('heroicon-o-clipboard-document-list')
                            ->schema([
                                Fieldset::make('')->columns(4)->schema([
                                    TextInput::make('task_rejection_times_red_card')
                                        ->label('Rejections times lead to red')
                                        ->default(2)
                                        ->prefixIconColor('danger')
                                        ->prefixIcon('heroicon-o-credit-card') // Replace with a red card icon class
                                        ->helperText('Red card indicates task rejection limit'), // Optional helper text

                                    TextInput::make('task_rejection_times_yello_card')
                                        ->label('Rejections times lead to yellow')
                                        ->prefixIconColor('warning')
                                        ->prefixIcon('heroicon-o-credit-card')
                                        ->helperText('Yellow card indicates task rejection limit')
                                        ->default(1),
                                    Select::make('task_red_card_penalty_type')->required()
                                    // ->text('-select a panality-')
                                        ->native(false)
                                        ->reactive()
                                        ->label('Penalty Type for Red Card')
                                        ->options([
                                            'deduction_half_day' => 'Deduction Half Day',
                                            'deduction_full_day' => 'Deduction Full Day',
                                            'custom_amount' => 'Custom amount',
                                            'no_penalty' => 'No Penalty',
                                        ])
                                        ->default('no_penalty')
                                        ->helperText('Select the penalty applied when a red card is issued'),
                                    TextInput::make('task_penality_custom_amount_red_card')
                                        ->label('Custom amount')
                                        ->visible(fn($get): bool => $get('task_red_card_penalty_type') == 'custom_amount')
                                        ->prefixIconColor('warning')
                                        ->prefixIconColor('danger')
                                        ->prefixIcon('heroicon-o-document-currency-dollar')
                                        ->helperText('Specify the deduction amount for employees who receive a red card')
                                        ->default(1),
                                    Checkbox::make('show_warning_message')
                                        ->inline(true)
                                        ->label('Show warning message before second rejection')
                                        ->default(false)
                                        ->helperText('Enable to show a warning message before issuing a second rejection'),

                                ]),
                                // Fieldset::make('')->columns(4)->schema([
                                //     TextInput::make('tasks_count_for_hero_title')
                                //         ->label('Tasks required for Hero title')
                                //         ->default(20) // Set a default value, if applicable
                                //         ->prefixIcon('heroicon-o-bars-arrow-up')
                                //         ->helperText('Specify the number of tasks an employee must complete to earn the "Hero of the Month" title.'),

                                // ]),
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
